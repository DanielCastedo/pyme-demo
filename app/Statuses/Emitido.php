<?php

namespace App\Statuses;

use App\Models\File;

class Emitido extends StatusBase implements StatusBaseInterface
{
    // definimos el permiso para el estado
    protected static string $authorization = 'case.status.emitido';

    // origenes validos para el estado
    protected static array $validSourceStatuses = [
        Procesado::class,
    ];

    // destinos validos para el estado
    protected static array $validFollowingStatuses = [
        Observado::class,
        Aprobado::class,
    ];

    public static function validate(File $file): void
    {
        // La lógica de origen ya vive en la base
        static::validateSourceStatus($file);
    }
}
