<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('almacenes')
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó el almacén {$this->name} ({$this->code})",
                'updated' => "Se actualizó el almacén {$this->name}",
                'deleted' => "Se eliminó el almacén {$this->name}",
                default => "Acción {$eventName} en almacén {$this->name}",
            });
    }

    protected $fillable = [
        'branche_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // La sucursal dueña siempre atiende su propio almacén (también al crearlo desde seeders o factories).
        static::saved(function (Warehouse $warehouse) {
            if ($warehouse->wasRecentlyCreated || $warehouse->wasChanged('branche_id')) {
                $warehouse->branches()->syncWithoutDetaching([$warehouse->branche_id]);
            }
        });
    }

    /** Sucursal dueña del almacén (la que lo administra). */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branche_id');
    }

    /** Sucursales que venden, compran y hacen pedidos con este almacén (incluye la dueña). */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_warehouse', 'warehouse_id', 'branch_id')->withTimestamps();
    }

    /** Almacenes que puede usar una sucursal: los suyos y los que comparte con otras. */
    public function scopeForBranch($query, int|string $branchId)
    {
        return $query->where(fn ($q) => $q->where('warehouses.branche_id', $branchId)
            ->orWhereHas('branches', fn ($b) => $b->where('branches.id', $branchId)));
    }

    /** @return array<int, int> */
    public function branchIds(): array
    {
        $ids = $this->relationLoaded('branches') ? $this->branches->pluck('id') : $this->branches()->pluck('branches.id');

        return $ids->push($this->branche_id)->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /**
     * Almacenes activos para los formularios de venta, pedido, consignación y compra, cada uno con "branch_ids"
     * (las sucursales que lo usan) para que la pantalla muestre solo los de la sucursal elegida.
     *
     * @return \Illuminate\Support\Collection<int, Warehouse>
     */
    public static function activeForForms(): \Illuminate\Support\Collection
    {
        return static::where('is_active', true)->with('branches:id')->orderBy('name')->get(['id', 'name', 'branche_id'])
            ->each(function (Warehouse $warehouse) {
                $warehouse->setAttribute('branch_ids', $warehouse->branchIds());
                $warehouse->unsetRelation('branches');
            });
    }

    public function servesBranch(int|string|null $branchId): bool
    {
        return $branchId !== null && in_array((int) $branchId, $this->branchIds(), true);
    }

    /** Nombres de las sucursales que lo usan, para listados ("Central, Norte"). */
    protected function getBranchNamesAttribute(): string
    {
        return $this->branches->pluck('name')->sort()->join(', ') ?: ($this->branch->name ?? '—');
    }

    /**
     * Sucursal a la que se le anota una operación (venta, compra, pedido, consignación) hecha con este almacén:
     * la elegida en el formulario; si no viene, la de la caja; si no, la del usuario; y si ninguna de esas usa
     * el almacén, su sucursal dueña.
     *
     * @throws \RuntimeException si la sucursal elegida no usa este almacén.
     */
    public function resolveBranchId(int|string|null $requestedBranchId, ?Cashbox $cashbox = null, ?User $user = null): int
    {
        if (! empty($requestedBranchId)) {
            if (! $this->servesBranch($requestedBranchId)) {
                $branch = Branch::find($requestedBranchId)?->name ?? "#{$requestedBranchId}";

                throw new \RuntimeException("La sucursal {$branch} no usa el almacén {$this->name}.");
            }

            return (int) $requestedBranchId;
        }

        foreach ([$cashbox?->branche_id, $user?->branche_id] as $candidate) {
            if ($this->servesBranch($candidate)) {
                return (int) $candidate;
            }
        }

        return (int) $this->branche_id;
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_warehouses')
            ->using(ProductWarehouse::class)
            ->withPivot('stock', 'min_stock', 'avg_cost')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
