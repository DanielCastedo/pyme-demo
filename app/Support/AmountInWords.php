<?php

namespace App\Support;

/**
 * Monto en letras para los recibos, al estilo de las facturas bolivianas:
 * 876.96 => "Ochocientos setenta y seis 96/100 Bolivianos".
 */
final class AmountInWords
{
    private const UNITS = [
        'cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece',
        'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós',
        'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
    ];

    private const TENS = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];

    private const HUNDREDS = [
        1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos', 5 => 'quinientos',
        6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos',
    ];

    /** "Ochocientos setenta y seis 96/100 Bolivianos". Los montos negativos se toman en valor absoluto. */
    public static function bolivianos(int|float|string|null $amount): string
    {
        $value = Bc::abs(Bc::round($amount, 2));
        [$integer, $cents] = array_pad(explode('.', Bc::round($value, 2), 2), 2, '00');

        return mb_strtoupper(mb_substr($words = self::integer((int) $integer), 0, 1)).mb_substr($words, 1)
            .' '.str_pad(substr($cents, 0, 2), 2, '0').'/100 Bolivianos';
    }

    /**
     * Número entero en letras. Antes de "mil" y "millones" se usa la forma apocopada (un, veintiún).
     *
     * @param  bool  $apocope  true cuando el número acompaña a "mil" o "millones" (21 => "veintiún")
     */
    public static function integer(int $n, bool $apocope = false): string
    {
        if ($n === 0) {
            return 'cero';
        }

        $parts = [];

        if ($n >= 1_000_000) {
            $millions = intdiv($n, 1_000_000);
            $parts[] = $millions === 1 ? 'un millón' : self::integer($millions, true).' millones';
            $n %= 1_000_000;
        }

        if ($n >= 1_000) {
            $thousands = intdiv($n, 1_000);
            $parts[] = $thousands === 1 ? 'mil' : self::integer($thousands, true).' mil';
            $n %= 1_000;
        }

        if ($n > 0) {
            $parts[] = self::belowThousand($n, $apocope);
        }

        return implode(' ', $parts);
    }

    private static function belowThousand(int $n, bool $apocope): string
    {
        if ($n === 100) {
            return 'cien';
        }

        $parts = [];

        if ($n >= 100) {
            $parts[] = self::HUNDREDS[intdiv($n, 100)];
            $n %= 100;
        }

        if ($n > 0) {
            $parts[] = self::belowHundred($n, $apocope);
        }

        return implode(' ', $parts);
    }

    private static function belowHundred(int $n, bool $apocope): string
    {
        if ($n < 30) {
            return match (true) {
                $apocope && $n === 1 => 'un',
                $apocope && $n === 21 => 'veintiún',
                default => self::UNITS[$n],
            };
        }

        $unit = $n % 10;

        return self::TENS[intdiv($n, 10)].($unit > 0 ? ' y '.($apocope && $unit === 1 ? 'un' : self::UNITS[$unit]) : '');
    }
}
