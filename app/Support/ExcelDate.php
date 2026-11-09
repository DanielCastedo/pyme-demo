<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Fechas leídas de un Excel (por ejemplo el vencimiento de un lote en las importaciones).
 */
class ExcelDate
{
    /**
     * Fecha en formato Y-m-d: una celda de fecha de Excel llega como número de serie (46387); escrita a mano,
     * como texto dd/mm/aaaa (también dd-mm-aaaa, aaaa-mm-dd o dd/mm/aa). Null si no es una fecha válida.
     */
    public static function toDate(string $value): ?string
    {
        $value = trim($value);

        if (is_numeric($value)) {
            $serial = (float) $value;

            // 2958465 = 31/12/9999, la última fecha que entiende Excel.
            return $serial >= 1 && $serial <= 2958465 ? Date::excelToDateTimeObject($serial)->format('Y-m-d') : null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            $date = \DateTime::createFromFormat('!'.$format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
