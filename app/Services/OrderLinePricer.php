<?php

namespace App\Services;

use App\Models\ProductUnit;
use App\Models\ProductWarehouse;
use App\Models\User;
use App\Support\Bc;

/**
 * Calcula precio, descuento y subtotal de una línea de venta o pedido. El precio del catálogo es la
 * base; si el usuario mandó otro precio, o un descuento, se exige el permiso correspondiente (nunca
 * se confía en lo que diga el formulario). El descuento puede ser % o monto fijo de toda la línea.
 */
class OrderLinePricer
{
    /**
     * @param  array{product_unit_id: int, quantity: float|string, unit_price?: float|string|null, discount_type?: string|null, discount_value?: float|string|null}  $line
     * @param  int|null  $warehouseId  almacén de donde sale la mercadería (para validar el precio contra el costo)
     * @return array{unit: ProductUnit, quantity: string, price: string, discount: string, subtotal: string}  montos como string decimal exacto (BCMath)
     *
     * @throws \RuntimeException
     */
    public function price(array $line, User $user, ?int $warehouseId = null): array
    {
        $unit = ProductUnit::with('product')->findOrFail($line['product_unit_id']);
        $quantity = Bc::round($line['quantity'], 2);
        $catalogPrice = Bc::round($unit->price, 2);

        $price = isset($line['unit_price']) && $line['unit_price'] !== '' ? Bc::round($line['unit_price'], 2) : $catalogPrice;
        $priceChanged = Bc::gt(Bc::abs(Bc::sub($price, $catalogPrice)), '0.004');

        if ($priceChanged && ! $user->can('ventas.editar-precio')) {
            throw new \RuntimeException('No tienes permiso para cambiar el precio de venta de "'.$unit->product->name.'".');
        }

        // Un precio cambiado no puede quedar por debajo del costo (promedio del almacén × lo que trae la presentación).
        // El precio del catálogo sin tocar se respeta aunque esté bajo el costo.
        if ($warehouseId && $priceChanged) {
            $baseCost = $unit->product->baseCostIn($warehouseId);
            $cost = Bc::round(Bc::mul($baseCost, $unit->factor), 2);

            if (Bc::gt($cost, 0) && Bc::lt(Bc::add($price, '0.004'), $cost)) {
                throw new \RuntimeException('El precio de "'.$unit->product->name.'" ('.number_format($price, 2).') no puede ser menor a su costo ('.number_format($cost, 2).').');
            }
        }

        $gross = Bc::round(Bc::mul($quantity, $price), 2);
        $value = Bc::num($line['discount_value'] ?? 0);
        $discount = '0';

        if (Bc::gt($value, 0)) {
            if (! $user->can('ventas.descuento')) {
                throw new \RuntimeException('No tienes permiso para aplicar descuentos.');
            }

            $type = $line['discount_type'] ?? 'amount';

            if ($type === 'percent' && Bc::gt($value, 100)) {
                throw new \RuntimeException('El descuento de "'.$unit->product->name.'" no puede pasar del 100%.');
            }

            $discount = $type === 'percent' ? Bc::round(Bc::div(Bc::mul($gross, $value), 100), 2) : Bc::round($value, 2);

            if (Bc::gt($discount, $gross)) {
                throw new \RuntimeException('El descuento de "'.$unit->product->name.'" ('.number_format($discount, 2).') supera el subtotal de la línea ('.number_format($gross, 2).').');
            }
        }

        return [
            'unit' => $unit,
            'quantity' => $quantity,
            'price' => $price,
            'discount' => $discount,
            'subtotal' => Bc::round(Bc::sub($gross, $discount), 2),
        ];
    }
}
