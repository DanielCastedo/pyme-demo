<?php

namespace App\Statuses;

use App\Models\File;

class Procesado extends StatusBase implements StatusBaseInterface
{
    // definimos el permiso para el estado
    protected static string $authorization = 'case.status.procesado';

    // origenes validos para el estado
    protected static array $validSourceStatuses = [
        Recepcionado::class,
    ];

    // destinos validos para el estado
    protected static array $validFollowingStatuses = [
        Emitido::class,
    ];

    public static function validate(File $file): void
    {
        // La lógica de origen ya vive en la base
        static::validateSourceStatus($file);
    }
}
