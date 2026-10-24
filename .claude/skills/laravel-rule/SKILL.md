---
name: laravel-rule
description: Crear reglas de validación personalizadas (custom Rule classes) en este proyecto Laravel (brazil_importaciones). Usar cuando una validación no se puede expresar con las reglas nativas de Laravel (stock disponible, unicidad compuesta, reglas de negocio, etc.).
---

# Custom Validation Rules — brazil_importaciones

Ubicación: `app/Rules/` (carpeta no existe todavía — créala al agregar la primera regla).
El proyecto usa **Laravel 10.50**, que soporta la interfaz moderna `ValidationRule` (no la vieja `Illuminate\Contracts\Validation\Rule`
con `passes()`/`message()` separados). Usa siempre la moderna.

## Estructura estándar

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StockDisponible implements ValidationRule
{
    public function __construct(private int $productId)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $producto = \App\Models\Product::find($this->productId);

        if (! $producto) {
            return;
        }

        if ($value > $producto->available_stock) {
            $fail("No hay suficiente stock disponible. Stock actual: {$producto->available_stock}.");
        }
    }
}
```

Uso dentro de un `FormRequest` (ver skill `laravel-request`):

```php
'quantity' => ['required', 'numeric', 'min:0.01', new StockDisponible($this->product_id)],
```

## Reglas del proyecto

- Nombre de clase en español o inglés según el dominio, pero descriptivo del negocio, no genérico
  (`StockDisponible`, `SkuUnicoPorEmpresa`, no `ValidQuantityRule`).
- Un archivo = una regla, `App\Rules\{Nombre}`.
- El mensaje de `$fail(...)` va siempre en español, mismo tono que los `messages()` de los Requests
  ("El código (SKU) es obligatorio.", directo y sin tecnicismos).
- Si la regla necesita datos externos (otro modelo, un ID de la petición), pásalos por constructor — no accedas a
  `request()` dentro de la regla.
- Usa reglas custom solo cuando las reglas nativas (`unique`, `exists`, `numeric`, `min`, etc.) o una combinación de ellas
  en el propio `rules()` del Request no alcanzan. No conviertas reglas nativas simples en clases custom sin necesidad.
- Si la validación requiere una consulta que ya se repite en 2+ Requests, ahí sí vale la pena extraerla a `app/Rules/` —
  antes de eso, déjala inline en el Request.
