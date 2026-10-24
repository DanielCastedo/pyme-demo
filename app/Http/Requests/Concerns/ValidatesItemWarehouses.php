<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

/**
 * Cada línea de venta/pedido/consignación puede traer su propio "warehouse_id" (si no, se asume el
 * almacén de la operación); esto reemplaza la regla "distinct" de product_unit_id, que ya no aplica
 * porque el mismo producto puede repetirse en dos líneas si sale de almacenes distintos.
 */
trait ValidatesItemWarehouses
{
    public function after(): array
    {
        return [function (Validator $validator) {
            $defaultWarehouseId = $this->input('warehouse_id');
            $items = (array) $this->input('items', []);
            $seen = [];

            foreach ($items as $i => $item) {
                $warehouseId = $item['warehouse_id'] ?? $defaultWarehouseId;
                $productUnitId = $item['product_unit_id'] ?? null;

                if ($productUnitId === null) {
                    continue;
                }

                $key = $productUnitId.'|'.$warehouseId;

                if (isset($seen[$key])) {
                    $validator->errors()->add("items.$i.product_unit_id", 'Ese producto ya está en el carrito con el mismo almacén: cambia la cantidad de esa línea en vez de repetirlo.');

                    continue;
                }

                $seen[$key] = true;
            }
        }];
    }
}
