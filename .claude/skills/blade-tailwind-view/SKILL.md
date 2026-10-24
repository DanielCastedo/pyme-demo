---
name: blade-tailwind-view
description: Crear o editar vistas Blade con Tailwind en este proyecto (brazil_importaciones), usando el kit de componentes existente en resources/views/components/ui. Usar para pantallas index/show/create/edit de cualquier recurso, modales, tablas, formularios y filtros.
---

# Vistas Blade + Tailwind (resource views) — brazil_importaciones

**No reinventes markup.** Este proyecto ya tiene un kit de componentes en `resources/views/components/ui/` (namespace
`x-ui.*`). Casi cualquier pantalla CRUD se arma combinando estos componentes, no escribiendo HTML/Tailwind desde cero.
Cada componente tiene un ejemplo de uso documentado en `resources/views/components/ui/uses_components/{nombre}.blade.php`
(léelo antes de usar un componente que no conozcas bien).

Layout raíz de toda página autenticada: `<x-app-layout> ... </x-app-layout>` (define `<title>`, sidebar, dark mode, etc. —
no lo reimplementes).

## Anatomía de una pantalla `index` (ver `resources/views/products/index.blade.php` como referencia completa)

```blade
<x-app-layout>
    <div class="max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
        @endif
        @if ($errors->any())
            <x-ui.alert type="error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @php
            $buttons = [
                ['id' => 'modal-toggle-button', 'label' => 'Nuevo Registro', 'variant' => 'primary',
                 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />'],
            ];
        @endphp

        <x-ui.page-header category="Módulo" title="Título de la Sección"
            description="Descripción corta de la pantalla." :buttons="$buttons" />

        <div class="mb-5">
            <x-ui.search-box placeholder="Buscar..." filterName="buscar" />
        </div>

        @php
            $columns = [
                ['key' => 'name', 'label' => 'Nombre'],
                ['key' => 'category.name', 'label' => 'Categoría'],
            ];
            $actions = [
                ['type' => 'view', 'route' => 'recurso.show'],
                ['type' => 'edit', 'route' => 'recurso.edit'],
                ['type' => 'delete', 'route' => 'recurso.destroy', 'confirm' => '¿Eliminar este registro?'],
            ];
        @endphp

        <x-ui.data-table :rows="$items" :columns="$columns" :actions="$actions" :paginator="$items"
            emptyText="No hay registros." />

        <x-ui.create-modal id="crud-modal" title="Registrar Nuevo Registro" :action="route('recurso.store')"
            submitText="Guardar" :columns="2" :fields="[
                ['type' => 'text', 'name' => 'name', 'label' => 'Nombre', 'required' => true],
            ]" />
    </div>

    @push('scripts')
        <script>
            const crudModal = document.getElementById('crud-modal');
            document.getElementById('modal-toggle-button')?.addEventListener('click', () => crudModal.classList.remove('hidden'));
        </script>
    @endpush
</x-app-layout>
```

## Catálogo de componentes (`x-ui.*`) y cuándo usar cada uno

- **`x-ui.page-header`** — encabezado de sección: `category`, `title`, `description`, `:buttons` (array de botones con
  `label`, `variant`, `icon` SVG-path, y `route` o `id` si abre un modal por JS).
- **`x-ui.search-box`** — buscador GET que preserva otros filtros de la query string. Props: `placeholder`, `filterName`
  (por defecto `buscar`, coincide con el `AllowedFilter::callback('buscar', ...)` del controlador).
- **`x-ui.filter-bar`** — barra de filtros adicionales (selects/fechas) que hace submit GET. Prop `fields` (mismo shape
  que `form-field`), `action` (normalmente la ruta del index actual), `resetRoute` opcional para "Limpiar filtros".
- **`x-ui.data-table`** — tabla con paginación y acciones. Props clave: `:rows`, `:columns` (array de
  `{key, label, type?}`; `type` soporta `badge-list`, `avatar-text`, `icon-text` además del texto plano por defecto —
  ver `uses_components/data-table.blade.php` para las variantes completas), `:actions` (presets `view`/`edit`/`manage`/
  `delete`, o acción custom con `label`/`class`/`icon` propios; `delete` acepta `confirm` para el `confirm()` de JS),
  `:paginator`, `emptyText`.
- **`x-ui.create-modal`** — modal de alta rápida sobre el index, como formulario embebido. Props: `id`, `title`,
  `:action` (route), `submitText`, `:columns` (1 o 2), `:hasFile` (true si hay un input file → agrega
  `enctype="multipart/form-data"`), `:fields` (ver forma de campo abajo). Se abre/cierra quitando/poniendo la clase
  `hidden` sobre el `id` del modal vía JS inline en `@push('scripts')` — no hay Alpine/Livewire para esto, es DOM plano.
- **`x-ui.create-modal-sidebar`** / **`x-ui.form-sidebar`** — variante en panel lateral en vez de modal centrado, mismo
  contrato de `fields`.
- **`x-ui.edit-page`** — página completa de edición (no modal), con volver/cancelar. Props: `title`, `category`,
  `backRoute`, `:action`, `method` (`PUT`/`PATCH`), `:fields`, `submitText`, `:columns`, `:hasFile`. Usa `old($name, $value)`
  automáticamente en cada campo — al pasar `fields`, pásale el `value` actual del modelo (`$product->name`, etc.), el
  componente ya prioriza `old()` sobre eso en caso de error de validación.
- **`x-ui.show-page`** — página de detalle. Props: `title`, `category`, `backRoute`, `editRoute`, `editText`, `:items`
  (array de `{label, value, fullWidth?, mono?}`), `:columns`. El `$slot` es para contenido extra debajo (tablas
  relacionadas, historial, etc.).
- **`x-ui.form-field`** — el input individual reutilizable (usado internamente por `create-modal`/`edit-page`, y también
  se usa suelto dentro de un `<form>` a mano, como en el modal de "Registrar Movimiento" de `products/index.blade.php`).
  Tipos soportados: `text`, `email`, `password`, `number`, `textarea`, `select` (con buscador tipo autocomplete
  integrado, no es un `<select>` nativo), `checkbox-grid`, `checkbox`, `radio`, `date`, `datetime-local`, `time`, `file`.
- **`x-ui.button`** — botón con variantes: `primary`, `secondary`, `danger`, `warning`, `success`, `outline`; tamaños
  `sm`/`md`/`lg`; soporta `:icon` (SVG path interno) e `iconPosition`.
- **`x-ui.alert`** — banners de `success`/`error`/`warning`/`info`, `dismissible` por defecto.
- **`x-ui.stat-card`** — tarjeta de KPI numérico simple: `label`, `value`, `color` (`gray`, `emerald`, `red`, `sky`,
  `amber`, `violet`).
- **`x-ui.status-filter-pills`**, **`x-ui.icon-picker`**, **`x-ui.import-modal`** / **`x-ui.product-import-modal`**,
  **`x-ui.movement-modal`**, **`x-ui.pos-*`**, **`x-ui.purchase-ticket`** — componentes especializados de módulos
  concretos (POS, importación Excel, kardex). Revisa el archivo fuente antes de copiarlos a otro módulo; varios están
  acoplados a `Product`/`Sale`.

### Forma estándar de un item de `fields` (create-modal / edit-page / filter-bar)

```php
['type' => 'text|number|select|textarea|checkbox|file|date', 'name' => 'campo', 'label' => 'Etiqueta',
 'placeholder' => '...', 'required' => true, 'value' => $model->campo ?? '', 'options' => [...], // para select
 'help' => 'Texto de ayuda', 'fullWidth' => true, 'rows' => 3 /* textarea */]
```

## Convenciones visuales del proyecto (si escribes markup a mano fuera del kit)

- Paleta de acento: **emerald** (`emerald-500/600`) para foco, éxito y elementos activos — no introduzcas otro color de
  marca (azul, morado) salvo que sea un color semántico puntual (`sky` para info/escaneo, `red` para peligro, `amber`
  para advertencia) igual que en `data-table`/`stat-card`.
- **Dark mode siempre presente**: cada clase de color lleva su contraparte `dark:` (`bg-white dark:bg-slate-900`,
  `text-gray-900 dark:text-white`, `border-gray-200 dark:border-slate-700`). No agregues un bloque sin su variante dark.
- Tarjetas: `bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-800 rounded-xl` (a veces `rounded-2xl`
  en modales) `shadow-sm`.
- Texto de labels de formulario: `text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5`, asterisco rojo
  (`text-red-500`) para requeridos.
- Errores de campo: `@error($name) border-red-500 @enderror` en el input + `<p class="text-xs text-red-500 mt-1">{{ $message }}</p>`
  con `@error($name) ... @enderror` debajo.
- Iconografía: SVG inline de Heroicons (outline), `stroke-linecap="round" stroke-linejoin="round" stroke-width="2"`,
  nunca una librería de iconos externa.
- Modales: `fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm`, contenido `bg-white dark:bg-slate-900 rounded-2xl
  border ... shadow-2xl`, cerrados por defecto con la clase `hidden` y abiertos quitándola vía `id` + JS plano en
  `@push('scripts')` — no se usa Alpine.js ni Livewire para el estado de UI en este proyecto pese a tener Livewire
  instalado en composer.json (revisa si el módulo que tocas ya usa Livewire antes de asumir uno u otro).
- Contenedor de página estándar: `max-w-10xl mx-auto px-4 sm:px-6 lg:px-8 py-8`.

## Roles y permisos en las vistas

Para ocultar botones/acciones según permiso, envuelve con `@can('editar products')` ... `@endcan` (ver skill
`spatie-roles-permissions`). El acceso a la ruta ya está protegido por middleware, pero si la vista es compartida entre
roles con distinto acceso (ej. un dashboard), oculta también el botón/acción en el Blade para no mostrar acciones que
luego fallarán con 403.
