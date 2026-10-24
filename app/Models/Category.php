<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('categorias')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó la categoría de productos {$this->name}",
                'updated' => "Se actualizó la categoría {$this->name}",
                'deleted' => "Se eliminó la categoría {$this->name}",
                default => "Acción {$eventName} en categoría {$this->name}",
            });
    }

    protected $fillable = [
        'name',
        'description',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
