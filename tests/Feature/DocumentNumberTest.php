<?php

use App\Models\Cashbox;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\Type;
use App\Services\TransactionService;

/*
|--------------------------------------------------------------------------
| Numeración correlativa de las transacciones (TRX-0001, TRX-0002...)
|--------------------------------------------------------------------------
| Los 4 dígitos son un mínimo de relleno, no un tope: después de TRX-9999 sigue TRX-10000 y así sucesivamente.
*/

beforeEach(function () {
    $this->seed(\Database\Seeders\TypeSeeder::class);
});

/** Registra un ingreso de caja de 10 Bs y devuelve la transacción. */
function registerTrx(Cashbox $cashbox, PaymentMethod $method): Transaction
{
    return app(TransactionService::class)->register([
        'type_id' => Type::where('code', 'VTA')->firstOrFail()->id,
        'payment_id' => $method->id,
        'cashbox_id' => $cashbox->id,
        'amount' => 10,
        'user_id' => userWith()->id,
    ]);
}

it('la primera transacción es TRX-0001 y sigue en orden', function () {
    $cashbox = Cashbox::factory()->create(['branche_id' => testBranch()->id]);
    $method = PaymentMethod::factory()->create(['is_cash' => true]);

    expect(registerTrx($cashbox, $method)->number)->toBe('TRX-0001')
        ->and(registerTrx($cashbox, $method)->number)->toBe('TRX-0002');
});

it('después de TRX-9999 sigue TRX-10000 (no se repite ni se corta)', function () {
    $cashbox = Cashbox::factory()->create(['branche_id' => testBranch()->id]);
    $method = PaymentMethod::factory()->create(['is_cash' => true]);

    $first = registerTrx($cashbox, $method);
    $first->update(['number' => 'TRX-9999']); // como si ya se hubieran hecho 9.999 transacciones

    $next = registerTrx($cashbox, $method);
    $after = registerTrx($cashbox, $method);

    expect($next->number)->toBe('TRX-10000')
        ->and($after->number)->toBe('TRX-10001')
        ->and(Transaction::pluck('number')->unique())->toHaveCount(3);
});

it('el número más alto se toma por orden de creación, no por orden alfabético (TRX-10000 va después de TRX-9999)', function () {
    $cashbox = Cashbox::factory()->create(['branche_id' => testBranch()->id]);
    $method = PaymentMethod::factory()->create(['is_cash' => true]);

    registerTrx($cashbox, $method)->update(['number' => 'TRX-9999']);
    registerTrx($cashbox, $method); // TRX-10000

    // Por texto, "TRX-9999" > "TRX-10000"; aun así el siguiente debe ser 10001
    expect(registerTrx($cashbox, $method)->number)->toBe('TRX-10001');
});
