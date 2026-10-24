<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('clientes')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registró al cliente {$this->name} (Doc: {$this->document})",
                'updated' => "Se actualizaron los datos del cliente {$this->name}",
                'deleted' => "Se eliminó al cliente {$this->name}",
                default => "Acción {$eventName} en cliente {$this->name}",
            });
    }

    public const DOCUMENT_TYPES = [
        'ci' => 'CI',
        'nit' => 'NIT',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'name',
        'document_type',
        'document',
        'phone',
        'email',
        'address',
        'city',
        'is_active',
        'credit_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
    ];

    /**
     * Lo que el cliente debe hoy: saldo de sus ventas a crédito no anuladas.
     */
    public function receivableBalance(): float
    {
        return (float) Bc::round($this->orders()
            ->where('payment_type', Order::PAYMENT_CREDIT)
            ->where('status', Order::STATUS_COMPLETED)
            ->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as owed')
            ->value('owed'), 2);
    }

    protected function getCreditLimitLabelAttribute(): string
    {
        return $this->credit_limit === null ? 'Sin límite' : number_format((float) $this->credit_limit, 2);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    /**
     * Alta o actualización del cliente desde el formulario de una proforma. El documento es único: si ya
     * existe un cliente con ese documento se reutiliza, y a un cliente existente solo se le cambia el
     * documento cuando no lo usa otro (para no chocar con el índice único).
     *
     * @param  array{customer_id?: int|null, customer_name: string, customer_document?: string|null, customer_phone?: string|null, customer_email?: string|null, customer_address?: string|null}  $data
     */
    public static function syncFromProforma(array $data): self
    {
        $document = trim((string) ($data['customer_document'] ?? '')) ?: null;

        $customer = ! empty($data['customer_id'])
            ? self::findOrFail($data['customer_id'])
            : ($document ? self::where('document', $document)->first() : null);

        $fields = [
            'name' => $data['customer_name'],
            'phone' => $data['customer_phone'] ?? null,
            'email' => $data['customer_email'] ?? null,
            'address' => $data['customer_address'] ?? null,
        ];

        $documentTaken = $document && self::where('document', $document)
            ->when($customer, fn ($q) => $q->whereKeyNot($customer->id))
            ->exists();

        if ($document && ! $documentTaken) {
            $fields['document'] = $document;
        }

        if ($customer) {
            $customer->update($fields);

            return $customer;
        }

        return self::create($fields + ['is_active' => true]);
    }
    /**
     * Búsqueda por nombre, documento, teléfono o correo.
     */
    public function scopeSearch(Builder $query, string $value): void
    {
        $query->where(function ($q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('document', 'like', "%{$value}%")
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
