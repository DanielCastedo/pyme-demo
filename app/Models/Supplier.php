<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Proveedor al que se le compra mercadería (ver Purchase).
 */
class Supplier extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('proveedores')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registró el proveedor {$this->name}",
                'updated' => "Se actualizó el proveedor {$this->name}",
                'deleted' => "Se eliminó el proveedor {$this->name}",
                default => "Acción {$eventName} en proveedor {$this->name}",
            });
    }

    public const DOCUMENT_TYPES = [
        'nit' => 'NIT',
        'ci' => 'CI',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'name',
        'document_type',
        'document',
        'contact_name',
        'phone',
        'email',
        'address',
        'city',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /**
     * Búsqueda por nombre, documento, contacto, teléfono o correo.
     */
    public function scopeSearch(Builder $query, string $value): void
    {
        $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('document', 'like', "%{$value}%")
                ->orWhere('contact_name', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%");
        });
    }

    protected function getDocumentLabelAttribute(): string
    {
        if (! $this->document) {
            return '—';
        }

        return (self::DOCUMENT_TYPES[$this->document_type] ?? '').' '.$this->document;
    }

    protected function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Activo' : 'Inactivo';
    }
}
