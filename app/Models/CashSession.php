<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Turno de caja de un usuario: se abre con un fondo inicial para dar cambio (efectivo que ya está en la caja, no una
 * venta) y se cierra contando el efectivo. Ver App\Services\CashSessionService.
 */
class CashSession extends Model
{
    protected $fillable = [
        'cashbox_id',
        'user_id',
        'opened_at',
        'opening_amount',
        'opening_note',
        'closed_at',
        'expected_cash',
        'counted_cash',
        'difference',
        'closing_note',
        'closed_by',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_amount' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'counted_cash' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(Cashbox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by')->withTrashed();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('closed_at');
    }

    public function isOpen(): bool
    {
        return $this->closed_at === null;
    }

    /** Turno abierto de un usuario (como mucho uno a la vez). */
    public static function openFor(int $userId): ?self
    {
        return static::open()->where('user_id', $userId)->first();
    }

    /**
     * Resumen del turno a partir de sus transacciones vigentes (no anuladas):
     *  - cash_in / cash_out: efectivo que entró (ventas, cobros) y que salió (gastos, pagos);
     *  - by_method: lo cobrado (entradas) por cada método de pago, efectivo y no efectivo;
     *  - expected_cash: fondo inicial + cash_in − cash_out (lo que debería haber en efectivo al cerrar);
     *  - sales_total: todo lo que entró (cualquier método); el fondo inicial NO cuenta como venta.
     *
     * @return array{opening: string, cash_in: string, cash_out: string, other_in: string, other_out: string, sales_total: string, expected_cash: string, count: int, by_method: array<string, string>}
     */
    public function summary(): array
    {
        $rows = $this->transactions()->where('canceled', false)->with('type', 'paymentMethod')->get();
        $sum = fn ($items) => Bc::round(Bc::sum($items->pluck('amount')), 2);

        $in = $rows->filter(fn ($t) => $t->type->sign === '+');
        $out = $rows->filter(fn ($t) => $t->type->sign === '-');
        $isCash = fn ($t) => (bool) $t->paymentMethod?->is_cash;

        $cashIn = $sum($in->filter($isCash));
        $cashOut = $sum($out->filter($isCash));

        return [
            'opening' => Bc::num($this->opening_amount),
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'other_in' => $sum($in->reject($isCash)),
            'other_out' => $sum($out->reject($isCash)),
            'sales_total' => $sum($in),
            'expected_cash' => Bc::round(Bc::sub(Bc::add($this->opening_amount, $cashIn), $cashOut), 2),
            'count' => $rows->count(),
            'by_method' => $in->groupBy(fn ($t) => $t->paymentMethod->name ?? 'Sin método')->map($sum)->sortKeys()->all(),
        ];
    }

    protected function getStatusLabelAttribute(): string
    {
        return $this->isOpen() ? 'Abierta' : 'Cerrada';
    }

    /** "Sobrante 5.00", "Faltante 3.50", "Cuadra" o "—" si sigue abierta. */
    protected function getDifferenceLabelAttribute(): string
    {
        if ($this->difference === null) {
            return '—';
        }

        return match (Bc::cmp($this->difference, 0)) {
            1 => 'Sobrante '.number_format((float) $this->difference, 2),
            -1 => 'Faltante '.number_format((float) Bc::abs($this->difference), 2),
            default => 'Cuadra',
        };
    }
}
