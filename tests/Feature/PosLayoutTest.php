<?php

/*
|--------------------------------------------------------------------------
| Punto de venta: diseño responsivo
|--------------------------------------------------------------------------
| Venta, pedido y consignación comparten el mismo punto de venta (sales.partials.pos). En pantallas chicas cada
| producto se muestra como tarjeta (foto y nombre, y debajo cantidad, precio, descuento y subtotal).
*/

it('las pantallas de venta, pedido y consignación incluyen el diseño responsivo de las líneas', function (string $route, array $permissions) {
    $this->actingAs(userWith(...$permissions));

    $this->get(route($route))
        ->assertOk()
        ->assertSee('id="pos-lines-table"', false)
        ->assertSee('@media (max-width: 767px)', false)
        ->assertSee('.price-hint', false);
})->with([
    'venta' => ['sales.create', ['ver sales', 'crear sales']],
    'pedido' => ['pedidos.create', ['ver orders', 'crear orders']],
    'consignación' => ['consignments.create', ['ver consignments', 'crear consignments']],
]);
