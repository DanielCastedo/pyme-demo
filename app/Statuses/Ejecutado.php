<?php

namespace App\Statuses;

use App\Models\File;

class Ejecutado extends StatusBase implements StatusBaseInterface
{
    // definimos el permiso para el estado
    protected static string $authorization = 'case.status.ejecutado';

    // origenes validos para el estado
    protected static array $validSourceStatuses = [
        Aprobado::class,
        Pagado::class,
    ];

    // destinos validos para el estado
    protected static array $validFollowingStatuses = [
        Archivado::class,
    ];

    public static function validate(File $file): void
    {
        // La lógica de origen ya vive en la base
        static::validateSourceStatus($file);
    }
}
