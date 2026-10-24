<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quotation extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('cotizaciones')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(function (string $eventName) {
                $client = $this->customer?->name ?? 'Cliente';
                return match ($eventName) {
                    'created' => "Se generó la proforma #{$this->number} para {$client} por Bs. " . number_format((float) $this->amount, 2),
                    'updated' => "Se actualizó la proforma #{$this->number} ({$client})",
                    'deleted' => "Se eliminó la proforma #{$this->number} ({$client})",
                    default => "Acción {$eventName} en proforma #{$this->number}",
                };
            });
    }

    protected $fillable = [
        'user_id',
        'customer_id',
        'number',
        'date',
        'expires_at',
        'status',
        'amount',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('d/m/Y') : '—';
    }

    public function getFormattedExpiresAtAttribute(): string
    {
        return $this->expires_at ? $this->expires_at->format('d/m/Y') : '—';
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Bs. ' . number_format((float) $this->amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'expired' => 'Vencida',
            default => 'Pendiente',
        };
    }
}
