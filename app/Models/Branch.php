<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('sucursales')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó la sucursal {$this->name}",
                'updated' => "Se actualizó la sucursal {$this->name}",
                'deleted' => "Se eliminó la sucursal {$this->name}",
                default => "Acción {$eventName} en sucursal {$this->name}",
            });
    }

    protected $fillable = [
        'name',
        'address',
        'phone',
        'is_active',
        'logo',
        'payment_qr_image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** URL del logo de la sucursal (se imprime en el encabezado de sus recibos). */
    protected function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('uploads')->url($this->logo) : null;
    }

    /** URL del QR de cobro de la sucursal (se muestra al cliente al pagar con QR en el punto de venta). */
    protected function getPaymentQrImageUrlAttribute(): ?string
    {
        return $this->payment_qr_image ? Storage::disk('uploads')->url($this->payment_qr_image) : null;
    }

    protected function getPaymentQrImageAbsoluteUrlAttribute(): ?string
    {
        return $this->payment_qr_image ? url(Storage::disk('uploads')->url($this->payment_qr_image)) : null;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'branche_id');
    }

    public function cashboxes(): HasMany
    {
        return $this->hasMany(Cashbox::class, 'branche_id');
    }

    /** Almacenes de los que es dueña. */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'branche_id');
    }

    /** Almacenes que usa (los suyos y los compartidos con otras sucursales). */
    public function usableWarehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'branch_warehouse', 'branch_id', 'warehouse_id')->withTimestamps();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'branche_id');
    }
}
