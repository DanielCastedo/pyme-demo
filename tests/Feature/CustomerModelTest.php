<?php

use App\Models\Customer;
use App\Models\Order;

/*
|--------------------------------------------------------------------------
| Customer (modelo): búsqueda, etiquetas, deuda y alta desde proformas
|--------------------------------------------------------------------------
*/

describe('scope search', function () {
    it('busca en nombre, documento, teléfono y correo', function () {
        Customer::factory()->create(['name' => 'Andina', 'document' => '111', 'phone' => '700', 'email' => 'a@a.test']);
        Customer::factory()->create(['name' => 'Beta', 'document' => '222', 'phone' => '800', 'email' => 'b@b.test']);

        expect(Customer::search('Andi')->pluck('name')->all())->toBe(['Andina'])
            ->and(Customer::search('222')->pluck('name')->all())->toBe(['Beta'])
            ->and(Customer::search('800')->pluck('name')->all())->toBe(['Beta'])
            ->and(Customer::search('a@a')->pluck('name')->all())->toBe(['Andina'])
            ->and(Customer::search('nada')->count())->toBe(0);
    });
});

describe('etiquetas', function () {
    it('arma la etiqueta del documento con su tipo', function () {
        $nit = Customer::factory()->make(['document_type' => 'nit', 'document' => '1023']);
        $sinDocumento = Customer::factory()->make(['document' => null]);

        expect($nit->document_label)->toBe('NIT 1023')
            ->and($sinDocumento->document_label)->toBe('—');
    });

    it('indica el estado y el límite de crédito', function () {
        expect(Customer::factory()->make(['is_active' => true])->status_label)->toBe('Activo')
            ->and(Customer::factory()->inactive()->make()->status_label)->toBe('Inactivo')
            ->and(Customer::factory()->make(['credit_limit' => null])->credit_limit_label)->toBe('Sin límite')
            ->and(Customer::factory()->make(['credit_limit' => 1500])->credit_limit_label)->toBe('1,500.00');
    });
});

describe('receivableBalance (deuda por ventas a crédito)', function () {
    it('suma el saldo de las ventas a crédito vigentes', function () {
        $customer = Customer::factory()->create();

        orderFor($customer, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 500, 'paid_amount' => 200]);
        orderFor($customer, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 300, 'paid_amount' => 0]);

        expect($customer->receivableBalance())->toBe(600.0);
    });

    it('no cuenta ventas al contado, anuladas ni pedidos en espera', function () {
        $customer = Customer::factory()->create();

        orderFor($customer, ['payment_type' => Order::PAYMENT_CASH, 'amount' => 900, 'paid_amount' => 900]);
        orderFor($customer, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 400, 'status' => Order::STATUS_CANCELED]);
        orderFor($customer, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 250, 'status' => Order::STATUS_PENDING]);

        expect($customer->receivableBalance())->toBe(0.0);
    });

    it('no mezcla la deuda de otros clientes', function () {
        [$a, $b] = Customer::factory()->count(2)->create();

        orderFor($a, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 100]);
        orderFor($b, ['payment_type' => Order::PAYMENT_CREDIT, 'amount' => 700]);

        expect($a->receivableBalance())->toBe(100.0)
            ->and($b->receivableBalance())->toBe(700.0);
    });
});

describe('syncFromProforma', function () {
    it('crea un cliente activo con los datos del formulario', function () {
        $customer = Customer::syncFromProforma([
            'customer_name' => 'Cliente Proforma',
            'customer_document' => 'PF-1',
            'customer_phone' => '700',
            'customer_email' => 'pf@test.test',
            'customer_address' => 'Calle 5',
        ]);

        expect($customer->exists)->toBeTrue()
            ->and($customer->fresh())->name->toBe('Cliente Proforma')
            ->document->toBe('PF-1')
            ->is_active->toBeTrue()
            ->document_type->toBe('ci');
    });

    it('reutiliza al cliente que ya tiene ese documento en vez de duplicarlo', function () {
        $existing = Customer::factory()->create(['name' => 'Nombre viejo', 'document' => 'PF-1']);

        $customer = Customer::syncFromProforma(['customer_name' => 'Nombre nuevo', 'customer_document' => 'PF-1']);

        expect($customer->id)->toBe($existing->id)
            ->and(Customer::count())->toBe(1)
            ->and($existing->fresh()->name)->toBe('Nombre nuevo');
    });

    it('actualiza al cliente elegido por id', function () {
        $existing = Customer::factory()->create(['name' => 'Viejo', 'phone' => '1']);

        Customer::syncFromProforma(['customer_id' => $existing->id, 'customer_name' => 'Nuevo', 'customer_phone' => '2']);

        expect($existing->fresh())->name->toBe('Nuevo')->phone->toBe('2');
    });

    it('no le quita el documento a otro cliente ni rompe el índice único', function () {
        Customer::factory()->create(['document' => 'DUP']);
        $mine = Customer::factory()->create(['document' => 'MIO']);

        $customer = Customer::syncFromProforma(['customer_id' => $mine->id, 'customer_name' => 'Editado', 'customer_document' => 'DUP']);

        expect($customer->fresh())->name->toBe('Editado')->document->toBe('MIO')
            ->and(Customer::where('document', 'DUP')->count())->toBe(1);
    });

    it('crea clientes sin documento sin chocar entre sí', function () {
        Customer::syncFromProforma(['customer_name' => 'Uno']);
        Customer::syncFromProforma(['customer_name' => 'Dos']);

        expect(Customer::count())->toBe(2);
    });

    it('falla si el cliente elegido por id ya no existe', function () {
        Customer::syncFromProforma(['customer_id' => 999, 'customer_name' => 'X']);
    })->throws(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
