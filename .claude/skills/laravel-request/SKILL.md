---
name: laravel-request
description: Crear o editar Form Requests (validación de entrada) en este proyecto Laravel (brazil_importaciones). Usar para requests Store/Update de cualquier recurso, o requests de import/acciones especiales.
---

# Form Requests — brazil_importaciones

Ubicación: `app/Http/Requests/`. Referencia real: `app/Http/Requests/StoreProductRequest.php` y `UpdateProductRequest.php`.

## Estructura estándar

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'related_id' => 'nullable|exists:relateds,id',
            'name'       => 'required|string|max:255',
            'amount'     => 'required|numeric|min:0',
            'active'     => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
        ];
    }
}
```

## Reglas del proyecto

- Un request por acción y por recurso: `Store{Modelo}Request` y `Update{Modelo}Request` (no un único request compartido con
  `sometimes`). Para acciones no-CRUD, nombra por la acción: `ImportProductsRequest`, `OpenCashRegisterRequest`,
  `CloseCashRegisterRequest`, `StoreCashMovementRequest`.
- `authorize()` devuelve `true` en todo el proyecto hoy — la autorización real ocurre en middleware de ruta
  (`permission:` / `role:`), no en el Request. No cambies esto a `$this->user()->can(...)` sin que el usuario lo pida, para
  no duplicar la autorización que ya existe en `routes/web.php`.
- **Reglas comunes usadas en el proyecto:**
  - Claves foráneas: `'nullable|exists:tabla,id'` o `'required|exists:tabla,id'`.
  - Unicidad en `Store`: `'required|string|max:X|unique:tabla,columna'`.
  - Unicidad en `Update` (excluyendo el registro actual): `'required|unique:tabla,columna,' . $this->route('parametro')->id`
    (mismo patrón que `RoleController@update`: `'unique:roles,name,' . $role->id`).
  - Dinero/cantidades: `'required|numeric|min:0'`.
  - Booleans de formulario: `'nullable|boolean'` (los checkboxes del UI kit envían `1` o nada).
  - Imágenes: `'nullable|image|mimes:jpg,jpeg,png,webp|max:8192'` (8MB, deja el comentario `// 8MB` como en el original).
  - Arrays de permisos/ids: `'nullable|array'`.
- `messages()` es opcional pero se usa cuando el mensaje por defecto de Laravel no es suficientemente claro en español;
  cuando lo agregues, escribe **todos** los mensajes en español, tono directo ("El código (SKU) es obligatorio.").
- No agregues `withValidator()` ni reglas condicionales complejas ahí — si la validación depende de una regla reusable o de
  lógica de consulta (ej. "el stock no puede exceder el disponible"), créala como Rule en `app/Rules/` (ver skill
  `laravel-rule`) y referénciala en el array de `rules()`.
- Para requests de importación de Excel (`Import*Request`), la regla típica es solo sobre el archivo:
  `'file' => 'required|file|mimes:xlsx,xls,csv|max:5120'`.
