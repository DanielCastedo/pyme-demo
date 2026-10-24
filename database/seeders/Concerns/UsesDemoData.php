<?php

namespace Database\Seeders\Concerns;

use App\Models\Cashbox;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Atajos para los seeders de ejemplo: el usuario, el almacén y la caja con los que se registra todo,
 * y buscar una presentación de producto por su SKU.
 */
trait UsesDemoData
{
    /** Quien "registra" los movimientos de ejemplo. */
    protected function admin(): User
    {
        return User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function warehouse(): Warehouse
    {
        return Warehouse::where('code', 'ALM-CEN')->firstOrFail();
    }

    /** La caja de la misma sucursal del almacén. */
    protected function cashbox(): Cashbox
    {
        return Cashbox::where('branche_id', $this->warehouse()->branche_id)->firstOrFail();
    }

    protected function cash(): PaymentMethod
    {
        return PaymentMethod::where('is_cash', true)->firstOrFail();
    }

    protected function customer(string $document): Customer
    {
        return Customer::where('document', $document)->firstOrFail();
    }

    /** Presentación de un producto por SKU. Sin nombre: la unidad base (la de factor 1). */
    protected function unit(string $sku, ?string $name = null): ProductUnit
    {
        return Product::where('sku', $sku)->firstOrFail()
            ->units()
            ->when($name, fn ($q) => $q->where('name', $name), fn ($q) => $q->where('is_base', true))
            ->firstOrFail();
    }
}
