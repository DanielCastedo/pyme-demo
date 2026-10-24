<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Gasto pagado desde una caja (ver App\Services\ExpenseService).
 */
class Expense extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gastos')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se registró el gasto '{$this->concept}' por Bs. " . number_format((float) $this->amount, 2),
                'updated' => "Se actualizó el gasto '{$this->concept}'",
                'deleted' => "Se anuló el gasto '{$this->concept}'",
                default => "Acción {$eventName} en gasto '{$this->concept}'",
            });
    }

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_LABELS = [
        self::STATUS_PAID => 'Pagado',
        self::STATUS_CANCELED => 'Anulado',
    ];

    protected $fillable = [
        'date', 'concept', 'description', 'amount', 'receipt_number', 'status',
        'expense_category_id', 'supplier_id', 'branche_id', 'user_id', 'transaction_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function isCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }

    protected function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    protected function getDateLabelAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    protected function getAmountLabelAttribute(): string
    {
        return number_format((float) $this->amount, 2);
    }

    protected function getCategoryLabelAttribute(): string
    {
        return $this->category?->name ?? 'Sin categoría';
    }
}
