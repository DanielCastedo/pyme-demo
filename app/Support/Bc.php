<?php

namespace App\Support;

/**
 * Aritmética de precisión arbitraria (BCMath) para dinero y cantidades.
 *
 * Todos los métodos reciben int|float|string|null y devuelven un string numérico exacto (null/'' cuentan como 0),
 * así se evitan los errores de coma flotante (0.1 + 0.2). Para mostrar en pantalla o enviar a JS se castea con (float)
 * o se usa number_format(), que aceptan estos strings.
 *
 * PHP 8.2 no trae bcround/bcfloor/bcceil (llegan en 8.4): aquí se implementan sobre bcadd/bcsub/bccomp
 * y se delega en las nativas cuando existan.
 */
final class Bc
{
    /** Decimales internos: sobra para cantidad × precio (4 + 4) antes de redondear al final. */
    public const SCALE = 10;

    /** Normaliza cualquier valor a un string decimal plano que BCMath acepte. */
    public static function num(mixed $value): string
    {
        if ($value === null || $value === '' || $value === false) {
            return '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            if (preg_match('/^[+-]?\d+(\.\d+)?$/', $value)) {
                return ltrim($value, '+');
            }
        }

        if (! is_numeric($value)) {
            return '0';
        }

        // float (o string en notación científica): se lleva a decimal plano
        $plain = rtrim(rtrim(number_format((float) $value, self::SCALE, '.', ''), '0'), '.');

        return in_array($plain, ['', '-', '-0'], true) ? '0' : $plain;
    }

    public static function add(mixed $a, mixed $b, int $scale = self::SCALE): string
    {
        return self::badd(self::num($a), self::num($b), $scale);
    }

    public static function sub(mixed $a, mixed $b, int $scale = self::SCALE): string
    {
        return self::bsub(self::num($a), self::num($b), $scale);
    }

    public static function mul(mixed $a, mixed $b, int $scale = self::SCALE): string
    {
        return self::bmul(self::num($a), self::num($b), $scale);
    }

    /** División: lanza DivisionByZeroError con divisor 0, igual que el operador / de PHP. */
    public static function div(mixed $a, mixed $b, int $scale = self::SCALE): string
    {
        return self::bdiv(self::num($a), self::num($b), $scale);
    }

    /** -1, 0 o 1. Compara con SCALE decimales. */
    public static function cmp(mixed $a, mixed $b, int $scale = self::SCALE): int
    {
        return self::bcmp(self::num($a), self::num($b), $scale);
    }

    public static function eq(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) === 0;
    }

    public static function gt(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) > 0;
    }

    public static function gte(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) >= 0;
    }

    public static function lt(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) < 0;
    }

    public static function lte(mixed $a, mixed $b): bool
    {
        return self::cmp($a, $b) <= 0;
    }

    public static function isZero(mixed $a): bool
    {
        return self::cmp($a, '0') === 0;
    }

    public static function max(mixed $a, mixed $b): string
    {
        return self::cmp($a, $b) >= 0 ? self::num($a) : self::num($b);
    }

    public static function min(mixed $a, mixed $b): string
    {
        return self::cmp($a, $b) <= 0 ? self::num($a) : self::num($b);
    }

    /** Suma una lista de valores (equivale a array_sum / Collection::sum). */
    public static function sum(iterable $values, int $scale = self::SCALE): string
    {
        $total = '0';

        foreach ($values as $value) {
            $total = self::badd($total, self::num($value), $scale);
        }

        return $total;
    }

    /** Redondea a $precision decimales, mitades hacia afuera del cero (como round() de PHP). */
    public static function round(mixed $value, int $precision = 0): string
    {
        $n = self::num($value);

        if (function_exists('bcround')) {
            return bcround($n, $precision);
        }

        $half = $precision > 0 ? '0.'.str_repeat('0', $precision).'5' : '0.5';

        return self::bcmp($n, '0', self::SCALE) < 0
            ? self::bsub($n, $half, $precision)
            : self::badd($n, $half, $precision);
    }

    public static function floor(mixed $value): string
    {
        $n = self::num($value);

        if (function_exists('bcfloor')) {
            return bcfloor($n);
        }

        $truncated = self::badd($n, '0', 0);

        return (self::bcmp($n, '0', self::SCALE) < 0 && self::bcmp($n, $truncated, self::SCALE) !== 0)
            ? self::bsub($truncated, '1', 0)
            : $truncated;
    }

    public static function ceil(mixed $value): string
    {
        $n = self::num($value);

        if (function_exists('bcceil')) {
            return bcceil($n);
        }

        $truncated = self::badd($n, '0', 0);

        return (self::bcmp($n, '0', self::SCALE) > 0 && self::bcmp($n, $truncated, self::SCALE) !== 0)
            ? self::badd($truncated, '1', 0)
            : $truncated;
    }

    /** Quita ceros y punto sobrantes para mostrar: "1.5000000000" => "1.5", "5.00" => "5". */
    public static function trim(mixed $value): string
    {
        $n = self::num($value);

        return str_contains($n, '.') ? rtrim(rtrim($n, '0'), '.') : $n;
    }

    /** Valor absoluto. */
    public static function abs(mixed $value): string
    {
        $n = self::num($value);

        return str_starts_with($n, '-') ? substr($n, 1) : $n;
    }

    /** ¿Es un número entero? (sin parte decimal). */
    public static function isInteger(mixed $value): bool
    {
        return self::cmp($value, self::floor($value)) === 0;
    }

    /** Entero PHP a partir del valor (trunca), para contadores/stock enteros. */
    public static function int(mixed $value): int
    {
        return (int) self::badd(self::num($value), '0', 0);
    }

    /*
     * Primitivas: usan la extensión bcmath si está instalada; si no (hosting sin ext-bcmath) caen a una
     * implementación PHP pura con la misma semántica (truncan al $scale, como bcmath).
     */

    private static function badd(string $a, string $b, int $scale): string
    {
        return function_exists('bcadd') ? \bcadd($a, $b, $scale) : self::soft('add', $a, $b, $scale);
    }

    private static function bsub(string $a, string $b, int $scale): string
    {
        return function_exists('bcsub') ? \bcsub($a, $b, $scale) : self::soft('sub', $a, $b, $scale);
    }

    private static function bmul(string $a, string $b, int $scale): string
    {
        return function_exists('bcmul') ? \bcmul($a, $b, $scale) : self::soft('mul', $a, $b, $scale);
    }

    private static function bdiv(string $a, string $b, int $scale): string
    {
        return function_exists('bcdiv') ? \bcdiv($a, $b, $scale) : self::soft('div', $a, $b, $scale);
    }

    private static function bcmp(string $a, string $b, int $scale): int
    {
        return function_exists('bccomp') ? \bccomp($a, $b, $scale) : (int) self::soft('cmp', $a, $b, $scale);
    }

    /** Descompone "-12.345" en [negativo, dígitos enteros sin punto, cantidad de decimales]. */
    private static function parts(string $n, int $scale): array
    {
        $neg = str_starts_with($n, '-');
        $n = ltrim($n, '+-');
        [$int, $frac] = array_pad(explode('.', $n, 2), 2, '');
        $frac = substr($frac, 0, $scale);

        return [$neg, ltrim($int.$frac, '0') ?: '0', strlen($frac)];
    }

    /** Aritmética decimal sobre strings, truncando a $scale decimales. */
    private static function soft(string $op, string $a, string $b, int $scale): string
    {
        // Para cmp se usa $scale como límite de decimales; para el resto se conservan todos hasta el resultado.
        $limit = $op === 'cmp' ? $scale : PHP_INT_MAX;
        [$na, $ia, $fa] = self::parts($a, $limit);
        [$nb, $ib, $fb] = self::parts($b, $limit);

        if ($op === 'mul') {
            return self::fmt($na !== $nb, self::umul($ia, $ib), $fa + $fb, $scale);
        }

        if ($op === 'div') {
            if ($ib === '0') {
                throw new \DivisionByZeroError('Division by zero');
            }

            // (A/10^fa) / (B/10^fb) = A*10^(fb+scale) / (B*10^fa), truncado a $scale decimales
            $num = $ia.str_repeat('0', $fb + $scale);
            $den = $ib.str_repeat('0', $fa);

            return self::fmt($na !== $nb, self::udiv($num, $den), $scale, $scale);
        }

        // add / sub / cmp: alinear a la misma cantidad de decimales
        $f = max($fa, $fb);
        $ia .= str_repeat('0', $f - $fa);
        $ib .= str_repeat('0', $f - $fb);

        if ($op === 'sub') {
            $nb = ! $nb;
        }

        if ($op === 'cmp') {
            // cero = solo ceros (tras alinear decimales '0' pasa a '000', por eso no se compara con '0')
            $za = ltrim($ia, '0') === '';
            $zb = ltrim($ib, '0') === '';
            $sa = $za ? 0 : ($na ? -1 : 1);
            $sb = $zb ? 0 : ($nb ? -1 : 1);

            if ($sa !== $sb) {
                return (string) ($sa <=> $sb);
            }

            return (string) (self::ucmp($ia, $ib) * $sa);
        }

        if ($na === $nb) {
            return self::fmt($na, self::uadd($ia, $ib), $f, $scale);
        }

        $c = self::ucmp($ia, $ib);

        if ($c === 0) {
            return self::fmt(false, '0', $f, $scale);
        }

        return $c > 0
            ? self::fmt($na, self::usub($ia, $ib), $f, $scale)
            : self::fmt($nb, self::usub($ib, $ia), $f, $scale);
    }

    /** Arma "-123.45" desde dígitos enteros con $f decimales implícitos, truncando/rellenando a $scale. */
    private static function fmt(bool $neg, string $digits, int $f, int $scale): string
    {
        if ($f > $scale) {
            $digits = substr($digits, 0, max(0, strlen($digits) - ($f - $scale)));
        } else {
            $digits .= str_repeat('0', $scale - $f);
        }

        $digits = str_pad(ltrim($digits, '0'), $scale + 1, '0', STR_PAD_LEFT);
        $int = substr($digits, 0, strlen($digits) - $scale);
        $out = $scale > 0 ? $int.'.'.substr($digits, -$scale) : $int;

        return ($neg && trim($digits, '0') !== '' ? '-' : '').$out;
    }

    private static function ucmp(string $a, string $b): int
    {
        $a = ltrim($a, '0');
        $b = ltrim($b, '0');

        return strlen($a) <=> strlen($b) ?: strcmp($a, $b) <=> 0;
    }

    private static function uadd(string $a, string $b): string
    {
        $len = max(strlen($a), strlen($b));
        $a = str_pad($a, $len, '0', STR_PAD_LEFT);
        $b = str_pad($b, $len, '0', STR_PAD_LEFT);
        $out = '';
        $carry = 0;

        for ($i = $len - 1; $i >= 0; $i--) {
            $s = (int) $a[$i] + (int) $b[$i] + $carry;
            $out = ($s % 10).$out;
            $carry = intdiv($s, 10);
        }

        return ($carry ? '1' : '').$out;
    }

    /** a - b, con a >= b. */
    private static function usub(string $a, string $b): string
    {
        $b = str_pad($b, strlen($a), '0', STR_PAD_LEFT);
        $out = '';
        $borrow = 0;

        for ($i = strlen($a) - 1; $i >= 0; $i--) {
            $d = (int) $a[$i] - (int) $b[$i] - $borrow;
            $borrow = $d < 0 ? 1 : 0;
            $out = ($d + $borrow * 10).$out;
        }

        return ltrim($out, '0') ?: '0';
    }

    private static function umul(string $a, string $b): string
    {
        $la = strlen($a);
        $lb = strlen($b);
        $r = array_fill(0, $la + $lb, 0);

        for ($i = $la - 1; $i >= 0; $i--) {
            for ($j = $lb - 1; $j >= 0; $j--) {
                $p = (int) $a[$i] * (int) $b[$j] + $r[$i + $j + 1];
                $r[$i + $j + 1] = $p % 10;
                $r[$i + $j] += intdiv($p, 10);
            }
        }

        return ltrim(implode('', $r), '0') ?: '0';
    }

    /** Cociente entero de a / b (b != 0), división larga. */
    private static function udiv(string $a, string $b): string
    {
        $b = ltrim($b, '0');
        $q = '';
        $rem = '0';

        for ($i = 0, $n = strlen($a); $i < $n; $i++) {
            $rem = ltrim($rem.$a[$i], '0') ?: '0';
            $d = 0;

            while (self::ucmp($rem, $b) >= 0) {
                $rem = self::usub($rem, $b);
                $d++;
            }

            $q .= $d;
        }

        return ltrim($q, '0') ?: '0';
    }
}
