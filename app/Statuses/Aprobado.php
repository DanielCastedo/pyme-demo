<?php

namespace App\Statuses;

use App\Models\File;

class Aprobado extends StatusBase implements StatusBaseInterface
{
    // definimos el permiso para el estado
    protected static string $authorization = 'case.status.aprobado';

    // origenes validos para el estado
    protected static array $validSourceStatuses = [
        Emitido::class,
    ];

    // destinos validos para el estado
    protected static array $validFollowingStatuses = [
        Pagado::class,
        Ejecutado::class,
    ];

    public static function validate(File $file): void
    {
        // La lógica de origen ya vive en la base
        static::validateSourceStatus($file);
    }
}
