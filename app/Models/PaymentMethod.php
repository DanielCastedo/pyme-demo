<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('metodos_pago')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó el método de pago {$this->name}",
                'updated' => "Se actualizó el método de pago {$this->name}",
                'deleted' => "Se eliminó el método de pago {$this->name}",
                default => "Acción {$eventName} en método de pago {$this->name}",
            });
    }

    /**
     * Se declara explícito porque el nombre de clase (PaymentMethod) no coincide con la
     * convención de tabla que usaría Eloquent por defecto ("payment_methods").
     */
    protected $table = 'payments';

    protected $fillable = [
        'name',
        'photo',
        'note',
        'number',
        'is_cash',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_id');
    }

    protected function getCashLabelAttribute(): string
    {
        return $this->is_cash ? 'Efectivo' : 'Digital / Banco';
    }

    protected function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('uploads')->url($this->photo) : null;
    }
}
