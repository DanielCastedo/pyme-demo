<?php

namespace App\Statuses;

use App\Models\File;

interface StatusBaseInterface
{
    public static function validate(File $file): void;
}
