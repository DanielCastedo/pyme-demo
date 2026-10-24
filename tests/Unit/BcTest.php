<?php

use App\Support\Bc;

/*
|--------------------------------------------------------------------------
| Bc: la implementación en PHP puro (que se usa cuando el servidor no tiene ext-bcmath)
| debe dar exactamente lo mismo que las funciones nativas de bcmath.
|--------------------------------------------------------------------------
*/

/** Ejecuta una operación por el fallback en PHP puro, sin pasar por la extensión. */
function softBc(string $op, string $a, string $b, int $scale): string
{
    $method = new ReflectionMethod(Bc::class, 'soft');
    $method->setAccessible(true);

    return (string) $method->invoke(null, $op, $a, $b, $scale);
}

$values = ['0', '0.00', '2', '2.00', '-2', '-2.50', '1800.00', '1802.00', '0.005', '99999.9999', '-0.5', '12.345678'];

it('el fallback en PHP puro coincide con bcmath en add, sub, mul, div y cmp', function (string $a, string $b, int $scale) {
    if (! function_exists('bcadd')) {
        $this->markTestSkipped('ext-bcmath no está instalada: no hay contra qué comparar.');
    }

    expect(softBc('add', $a, $b, $scale))->toBe(bcadd($a, $b, $scale))
        ->and(softBc('sub', $a, $b, $scale))->toBe(bcsub($a, $b, $scale))
        ->and(softBc('mul', $a, $b, $scale))->toBe(bcmul($a, $b, $scale))
        ->and((int) softBc('cmp', $a, $b, $scale))->toBe(bccomp($a, $b, $scale));

    if (bccomp($b, '0', 10) !== 0) {
        expect(softBc('div', $a, $b, $scale))->toBe(bcdiv($a, $b, $scale));
    }
})->with(function () use ($values) {
    foreach ($values as $a) {
        foreach ($values as $b) {
            yield "$a y $b" => [$a, $b, 2];
            yield "$a y $b (10 dec.)" => [$a, $b, 10];
        }
    }
});

it('compara bien contra cero (0.00 no es menor que 0): caso del saldo digital de una caja', function () {
    // Caja con 1800.00 de saldo y 1800.00 en efectivo, cobro de 2.00 en efectivo:
    // el saldo digital resultante es exactamente 0.00 y NO debe considerarse negativo.
    $digital = Bc::round(Bc::sub('1802.00', '1802.00'), 2);

    expect(Bc::lt($digital, 0))->toBeFalse()
        ->and(Bc::eq($digital, 0))->toBeTrue()
        ->and(Bc::isZero($digital))->toBeTrue()
        ->and((int) softBc('cmp', '0.00', '0', 10))->toBe(0)
        ->and((int) softBc('cmp', '0', '0.00', 10))->toBe(0)
        ->and((int) softBc('cmp', '-1.00', '0', 10))->toBe(-1)
        ->and((int) softBc('cmp', '0.00', '-1', 10))->toBe(1);
});
