---
name: laravel-migration
description: Crear o modificar migraciones en este proyecto Laravel (brazil_importaciones). Usar cuando se pida crear una tabla, agregar/quitar columnas, foreign keys, índices, o cualquier cambio de esquema de base de datos.
---

# Migraciones — brazil_importaciones

## ⚠️ Antes de escribir nada: el esquema real puede no coincidir con `database/migrations`

Este proyecto arrancó de un scaffold genérico y fue adaptado al dominio actual (ferretería / importadora).
Hay migraciones **huérfanas** que ya no reflejan el modelo real. Ejemplo confirmado: `2024_01_01_000009_create_products_table.php`
crea `price_sale`, `price_purchace` y `category_id`, pero el modelo `Product` real usa `sale_price`, `purchase_price` y
`product_category_id` (ver `app/Models/Product.php`). Hay más migraciones del mismo lote (`product_units`, `product_warehouses`,
`product_components`, `promotion_products`, `quotations`, `stock_movements`, etc.) que no tienen controlador, modelo ni vista asociada.

**Regla:** antes de crear una migración para una tabla que ya existe, verifica el esquema real, no confíes en el archivo de migración:

```bash
php artisan tinker --execute="print_r(Schema::getColumnListing('products'));"
```

O revisa directamente el `$fillable`/`$casts` del modelo en `app/Models/`, que es la fuente de verdad más confiable en este proyecto.
Si vas a tocar una tabla del "lote viejo" (sin modelo/controlador activo), pregunta al usuario si sigue en uso antes de modificarla.

## Convenciones de este proyecto

- Nombre de archivo: `YYYY_MM_DD_HHMMSS_create_<tabla_plural>_table.php` para tablas nuevas, o
  `YYYY_MM_DD_HHMMSS_add_<columna>_to_<tabla>_table.php` para alteraciones. Genera el timestamp con `php artisan make:migration`,
  no lo inventes a mano.
- Usa `Schema::create` / `Schema::table` con `Blueprint`, estilo estándar de Laravel 10 (clase anónima `return new class extends Migration`).
- `$table->id()` + `$table->timestamps()` en (casi) toda tabla nueva.
- Dinero/cantidades: `$table->decimal('campo', 10, 2)` — coincide con el cast `'campo' => 'decimal:2'` que llevará el modelo.
- Booleans de estado: `$table->boolean('active')->default(true)` (el proyecto usa `active`, no `is_active`, en las tablas nuevas del dominio actual — revisa el modelo antes de nombrar).
- Foreign keys: `$table->foreignId('product_category_id')->nullable()->constrained('product_categories')->nullOnDelete();`
  o `->constrained()->cascadeOnDelete()` cuando el borrado en cascada es intencional (revisa impacto en negocio antes de usar cascade en tablas transaccionales como ventas/compras).
- No recrees las tablas de Spatie (`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`) —
  ya existen en `database/migrations/2026_01_20_010347_create_permission_tables.php`.
- Para agregar una columna a una tabla que ya migró en producción/local, crea una migración `add_x_to_y_table` nueva.
  No edites una migración de `create_` que ya corrió — eso desincroniza el historial de otros entornos.

## Después de crear la migración

- Ejecuta `php artisan migrate` localmente para verificar que corre sin errores antes de dar la tarea por terminada.
- Si la migración toca una tabla con datos existentes (agregar columna `NOT NULL` sin default, por ejemplo), avisa al usuario del riesgo antes de correrla — no asumas que la tabla está vacía.
