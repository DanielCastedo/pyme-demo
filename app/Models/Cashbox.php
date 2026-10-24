<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cashbox extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('cajas')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó la caja {$this->name}",
                'updated' => "Se actualizó el saldo/estado de la caja {$this->name}",
                'deleted' => "Se eliminó la caja {$this->name}",
                default => "Acción {$eventName} en caja {$this->name}",
            });
    }

    protected $fillable = [
        'branche_id',
        'name',
        'cash',
        'balance',
    ];

    protected $casts = [
        'cash' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Total acumulado por tipo de transacción (solo las vigentes, no las anuladas),
     * opcionalmente dentro de un rango de fechas. Se calcula desde `transactions`
     * (única fuente de verdad), así nunca se desfasa.
     *
     * @return Collection<int, array{type: Type, count: int, total: float}>
     */
    public function totalsByType(?string $from = null, ?string $to = null): Collection
    {
        $rows = $this->transactions()
            ->where('canceled', false)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->selectRaw('type_id, COUNT(*) as movements, SUM(amount) as total')
            ->groupBy('type_id')
            ->get();

        $types = Type::whereIn('id', $rows->pluck('type_id'))->get()->keyBy('id');

        return $rows
            ->map(fn ($row) => [
                'type' => $types[$row->type_id],
                'count' => (int) $row->movements,
                'total' => (float) $row->total,
            ])
            ->sortBy(fn ($row) => $row['type']->name)
            ->values();
    }

    protected function getCashLabelAttribute(): string
    {
        return number_format((float) $this->cash, 2);
    }

    protected function getBalanceLabelAttribute(): string
    {
        return number_format((float) $this->balance, 2);
    }
}
