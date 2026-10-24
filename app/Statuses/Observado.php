<?php

namespace App\Statuses;

use App\Models\File;

class Observado extends StatusBase implements StatusBaseInterface
{
    // definimos el permiso para el estado
    protected static string $authorization = 'case.status observado';

    // origenes validos para el estado
    protected static array $validSourceStatuses = [];

    // destinos validos para el estado
    protected static array $validFollowingStatuses = [];

    public static function validate(File $file): void
    {
        // La lógica de origen ya vive en la base
        static::validateSourceStatus($file);
    }
}
