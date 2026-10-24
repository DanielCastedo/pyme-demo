<?php

namespace App\Models;

use App\Support\Bc;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('productos')
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => match ($eventName) {
                'created' => "Se creó el producto {$this->name} (SKU: {$this->sku})",
                'updated' => "Se actualizó el producto {$this->name}",
                'deleted' => "Se eliminó el producto {$this->name}",
                default => "Acción {$eventName} en producto {$this->name}",
            });
    }

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'color',
        'marca',
        'medida',
        'photo',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'photo_url',
    ];

    /**
     * Búsqueda por SKU, nombre o categoría (la usan el listado y la exportación a Excel).
     */
    public function scopeSearch(Builder $query, string $value): void
    {
        $query->where(function ($q) use ($value) {
            $q->where('sku', 'like', "%{$value}%")
                ->orWhere('name', 'like', "%{$value}%")
                ->orWhere('marca', 'like', "%{$value}%")
                ->orWhere('color', 'like', "%{$value}%")
                ->orWhere('medida', 'like', "%{$value}%")
                ->orWhereHas('category', fn ($cat) => $cat->where('name', 'like', "%{$value}%"));
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function baseUnit(): HasMany
    {
        return $this->hasMany(ProductUnit::class)->where('is_base', true);
    }

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(Warehouse::class, 'product_warehouses')
            ->using(ProductWarehouse::class)
            ->withPivot('stock', 'min_stock', 'avg_cost')
            ->withTimestamps();
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products')
            ->using(PromotionProduct::class)
            ->withTimestamps();
    }

    /**
     * Componentes que arman este producto cuando se vende como kit.
     */
    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'product_id');
    }

    /**
     * Kits en los que este producto se usa como componente.
     */
    public function usedAsComponentIn(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_product_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function quotationItems(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function stockMovementItems(): HasMany
    {
        return $this->hasMany(StockMovementItem::class);
    }

    /**
     * Retorna el stock disponible de este producto en un almacén.
     * Si es un producto tipo Kit/Lote (tiene componentes asociados), calcula dinámicamente
     * el máximo de combos armables evaluando el stock real de sus componentes.
     */
    public function getStockInWarehouse(?Warehouse $warehouse): float
    {
        if (! $warehouse) {
            return 0.0;
        }

        if ($this->relationLoaded('components') ? $this->components->isNotEmpty() : $this->components()->exists()) {
            $components = $this->relationLoaded('components') ? $this->components : $this->components()->get();
            if ($components->isEmpty()) {
                return 0.0;
            }

            $maxKits = $components->map(function ($comp) use ($warehouse) {
                $compStock = Bc::num(ProductWarehouse::where('product_id', $comp->component_product_id)
                    ->where('warehouse_id', $warehouse->id)
                    ->value('stock') ?? 0);
                $required = Bc::gt($comp->quantity, 0) ? Bc::num($comp->quantity) : '1';

                return Bc::floor(Bc::div($compStock, $required));
            })->reduce(fn ($min, $kits) => $min === null ? $kits : Bc::min($min, $kits));

            return (float) Bc::max(0, $maxKits);
        }

        return (float) (ProductWarehouse::where('product_id', $this->id)
            ->where('warehouse_id', $warehouse->id)
            ->value('stock') ?? 0);
    }

    protected function getTotalStockAttribute(): int
    {
        if ($this->relationLoaded('components') ? $this->components->isNotEmpty() : $this->components()->exists()) {
            $components = $this->relationLoaded('components') ? $this->components : $this->components()->get();
            if ($components->isEmpty()) {
                return 0;
            }

            $maxKits = $components->map(function ($comp) {
                $compStock = Bc::num(ProductWarehouse::where('product_id', $comp->component_product_id)->sum('stock'));
                $required = Bc::gt($comp->quantity, 0) ? Bc::num($comp->quantity) : '1';

                return Bc::floor(Bc::div($compStock, $required));
            })->reduce(fn ($min, $kits) => $min === null ? $kits : Bc::min($min, $kits));

            return Bc::int(Bc::max(0, $maxKits));
        }

        // Con la relación ya cargada (listados) se suma en memoria; si no, una consulta agregada.
        return (int) ($this->relationLoaded('warehouses')
            ? $this->warehouses->sum('pivot.stock')
            : $this->warehouses()->sum('product_warehouses.stock'));
    }

    public function isKit(): bool
    {
        return $this->relationLoaded('components') ? $this->components->isNotEmpty() : $this->components()->exists();
    }

    /**
     * Partes de un kit con lo que hay de cada una y cuántos kits alcanza (null si no es un kit).
     * Con almacén, el stock es el de ese almacén; sin él, el total de todos. El kit alcanza para el menor de sus partes.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, required: string, stock: string, kits: string, limits: bool}>|null
     */
    public function kitBreakdown(?int $warehouseId = null): ?\Illuminate\Support\Collection
    {
        if (! $this->isKit()) {
            return null;
        }

        $components = $this->relationLoaded('components') ? $this->components : $this->components()->get();
        $components->loadMissing('component');

        $parts = $components->map(function ($comp) use ($warehouseId) {
            $stock = Bc::num(ProductWarehouse::where('product_id', $comp->component_product_id)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->sum('stock'));
            $required = Bc::gt($comp->quantity, 0) ? Bc::num($comp->quantity) : '1';

            return [
                'name' => $comp->component->name ?? '—',
                'required' => $required,
                'stock' => $stock,
                'kits' => Bc::floor(Bc::div($stock, $required)),
                'limits' => false,
            ];
        })->values();

        $min = $parts->reduce(fn ($carry, $p) => $carry === null ? $p['kits'] : Bc::min($carry, $p['kits']));

        return $parts->map(fn ($p) => [...$p, 'limits' => Bc::eq($p['kits'], $min)]);
    }

    /**
     * Texto de stock para listados: el total, o en un kit "N kits" con lo que alcanza cada parte.
     */
    protected function getStockLabelAttribute(): string
    {
        $parts = $this->kitBreakdown();

        if ($parts === null) {
            return (string) $this->total_stock;
        }

        return $this->total_stock.' kits ('.$parts->map(fn ($p) => $p['name'].' '.Bc::int($p['stock']))->implode(' · ').')';
    }

    /**
     * Costo por unidad base en un almacén: el costo promedio del producto o, en un kit, la suma del costo de sus partes.
     */
    public function baseCostIn(int $warehouseId): string
    {
        if ($this->isKit()) {
            $components = $this->relationLoaded('components') ? $this->components : $this->components()->get();

            return Bc::sum($components->map(fn ($c) => Bc::mul(
                ProductWarehouse::where('product_id', $c->component_product_id)->where('warehouse_id', $warehouseId)->value('avg_cost'),
                $c->quantity
            )));
        }

        return Bc::num(ProductWarehouse::where('product_id', $this->id)->where('warehouse_id', $warehouseId)->value('avg_cost'));
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('uploads')->url($this->photo) : null;
    }

    /**
     * Entrada de este producto en un almacén: crea un lote nuevo (ver ProductWarehouse::receiveStock()).
     */
    public function receiveStock(Warehouse $warehouse, float $quantity, float $unitCost, ?int $itemId = null, $receivedAt = null): StockBatch
    {
        return ProductWarehouse::receiveStock($this->id, $warehouse->id, $quantity, $unitCost, $itemId, $receivedAt);
    }

    /**
     * Salida FIFO de este producto en un almacén; devuelve el costo consumido (ver ProductWarehouse::removeStock()).
     */
    public function removeStock(Warehouse $warehouse, float $quantity, ?int $itemId = null): float
    {
        return ProductWarehouse::removeStock($this->id, $warehouse->id, $quantity, $itemId);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }
}
