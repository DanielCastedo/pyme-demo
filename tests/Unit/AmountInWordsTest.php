<?php

use App\Support\AmountInWords;

/*
|--------------------------------------------------------------------------
| Monto en letras de los recibos ("Son: ...")
|--------------------------------------------------------------------------
*/

it('escribe el monto en letras al estilo de las facturas bolivianas', function (string|int|float|null $amount, string $expected) {
    expect(AmountInWords::bolivianos($amount))->toBe($expected);
})->with([
    'el ejemplo de la factura' => [876.96, 'Ochocientos setenta y seis 96/100 Bolivianos'],
    'cero' => [0, 'Cero 00/100 Bolivianos'],
    'solo centavos' => ['0.50', 'Cero 50/100 Bolivianos'],
    'uno' => [1, 'Uno 00/100 Bolivianos'],
    'un centavo' => ['1.01', 'Uno 01/100 Bolivianos'],
    'diez' => [10, 'Diez 00/100 Bolivianos'],
    'quince' => [15, 'Quince 00/100 Bolivianos'],
    'veintiuno' => [21, 'Veintiuno 00/100 Bolivianos'],
    'veintinueve' => [29, 'Veintinueve 00/100 Bolivianos'],
    'treinta y uno' => [31, 'Treinta y uno 00/100 Bolivianos'],
    'noventa y nueve' => [99, 'Noventa y nueve 00/100 Bolivianos'],
    'cien' => [100, 'Cien 00/100 Bolivianos'],
    'ciento uno' => [101, 'Ciento uno 00/100 Bolivianos'],
    'doscientos' => [200, 'Doscientos 00/100 Bolivianos'],
    'quinientos' => [500, 'Quinientos 00/100 Bolivianos'],
    'novecientos noventa y nueve' => [999, 'Novecientos noventa y nueve 00/100 Bolivianos'],
    'mil' => [1000, 'Mil 00/100 Bolivianos'],
    'mil uno' => [1001, 'Mil uno 00/100 Bolivianos'],
    'mil cien' => [1100, 'Mil cien 00/100 Bolivianos'],
    'dos mil' => [2000, 'Dos mil 00/100 Bolivianos'],
    'veintiún mil' => [21000, 'Veintiún mil 00/100 Bolivianos'],
    'treinta y un mil' => [31000, 'Treinta y un mil 00/100 Bolivianos'],
    'veintiún mil uno' => [21001, 'Veintiún mil uno 00/100 Bolivianos'],
    'ciento veintitrés mil' => [123000, 'Ciento veintitrés mil 00/100 Bolivianos'],
    'un millón' => [1000000, 'Un millón 00/100 Bolivianos'],
    'un millón y pico' => ['1234567.89', 'Un millón doscientos treinta y cuatro mil quinientos sesenta y siete 89/100 Bolivianos'],
    'dos millones' => [2000000, 'Dos millones 00/100 Bolivianos'],
    'veintiún millones' => [21000000, 'Veintiún millones 00/100 Bolivianos'],
    'redondea los centavos' => [10.999, 'Once 00/100 Bolivianos'],
    'string con decimales' => ['125.50', 'Ciento veinticinco 50/100 Bolivianos'],
    'negativo en valor absoluto' => [-5, 'Cinco 00/100 Bolivianos'],
    'nulo' => [null, 'Cero 00/100 Bolivianos'],
]);
