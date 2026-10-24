<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Promotion extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('promociones')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó la promoción {$this->name}",
                'updated' => "Se actualizó la promoción {$this->name}",
                'deleted' => "Se eliminó la promoción {$this->name}",
                default => "Acción {$eventName} en promoción {$this->name}",
            });
    }

    protected $fillable = [
        'name',
        'value',
        'min_quantity',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function getStartDateLabelAttribute(): string
    {
        return $this->start_date?->format('d/m/Y') ?? '—';
    }

    protected function getEndDateLabelAttribute(): string
    {
        return $this->end_date?->format('d/m/Y') ?? '—';
    }

    protected $casts = [
        'value' => 'decimal:2',
        'min_quantity' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products')
            ->using(PromotionProduct::class)
            ->withTimestamps();
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active
            && now()->between($this->start_date, $this->end_date);
    }
}
