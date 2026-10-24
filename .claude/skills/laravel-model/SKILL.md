---
name: laravel-model
description: Crear o editar modelos Eloquent en este proyecto Laravel (brazil_importaciones). Usar cuando se pida crear un modelo nuevo, agregar relaciones, accesores, casts o métodos de dominio a un modelo existente.
---

# Modelos Eloquent — brazil_importaciones

Ubicación: `app/Models/`. Modelo de referencia a imitar: `app/Models/Product.php`.

## Estructura estándar

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Example extends Model
{
    use HasFactory;

    protected $fillable = [
        'related_id',
        'name',
        // ...
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function related(): BelongsTo
    {
        return $this->belongsTo(Related::class, 'related_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExampleItem::class);
    }
}
```

## Reglas del proyecto

- Siempre `use HasFactory;`. No se usa `$guarded = []`; siempre `$fillable` explícito y completo.
- `$casts`: dinero/cantidades como `'decimal:2'`, flags como `'boolean'`. No uses el nuevo `Attribute::make()` de Laravel —
  el proyecto usa el estilo clásico de casts + accessors (`protected function getXAttribute()`), mantén consistencia.
- Tipar el retorno de las relaciones (`BelongsTo`, `HasMany`, `BelongsToMany`, etc.). Si la FK no sigue la convención
  `singular_id`, indícala explícitamente como segundo argumento: `belongsTo(ProductCategory::class, 'product_category_id')`.
- Accesores calculados (no persistidos) van como métodos `protected function getXxxAttribute(): tipo`, ejemplo real:
  `getAvailableStockAttribute()`, `getImageUrlAttribute()` en `Product`. Úsalos para valores derivados, no para lógica de negocio compleja.
- Métodos de dominio simples y con nombre expresivo directamente en el modelo cuando son consultas/reglas sobre sus propios
  atributos (ej. `hasLowStock(): bool`). No crees Service/Repository classes salvo que el usuario lo pida — este proyecto
  mantiene la lógica simple en el modelo o el controlador.
- Si el modelo necesita roles/permisos (normalmente solo `User`), agrega `use Spatie\Permission\Traits\HasRoles;` y el trait
  `use HasRoles;` en la clase — así está hecho en `app/Models/User.php`. No agregues `HasRoles` a otros modelos salvo que el
  usuario indique explícitamente que ese modelo representa un "actor" con roles.
- Antes de asumir el nombre de una columna, revisa la migración/tabla real (ver skill `laravel-migration` sobre migraciones
  huérfanas) — no copies nombres de columnas de migraciones viejas del scaffold sin verificar.
- No agregues soft deletes, UUIDs, observers ni traits extra si el usuario no los pidió — el proyecto no los usa por defecto.
