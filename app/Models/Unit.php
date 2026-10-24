<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unidad de medida del catálogo (Unidad, Caja, Docena, Kilogramo...). Cada presentación de un producto
 * (ProductUnit) elige una de estas unidades.
 */
class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'abbreviation', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Al renombrar una unidad, el nombre copiado en sus presentaciones se mantiene al día.
        static::updated(function (Unit $unit) {
            if ($unit->wasChanged('name')) {
                ProductUnit::where('unit_id', $unit->id)->update(['name' => $unit->name]);
            }
        });
    }

    public function productUnits(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * La unidad con ese nombre (sin distinguir mayúsculas) o una nueva si no existe.
     */
    public static function findOrCreateByName(string $name): self
    {
        $name = trim($name);

        return self::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? self::create(['name' => $name]);
    }

    protected function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Activa' : 'Inactiva';
    }

    protected function getDisplayNameAttribute(): string
    {
        return $this->abbreviation ? "{$this->name} ({$this->abbreviation})" : $this->name;
    }
}
