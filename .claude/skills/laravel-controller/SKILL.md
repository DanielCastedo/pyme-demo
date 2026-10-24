---
name: laravel-controller
description: Crear o editar controladores en este proyecto Laravel (brazil_importaciones). Usar para CRUD de recursos, listados con filtros/orden, import/export Excel, y acciones tipo POS/caja/inventario.
---

# Controladores — brazil_importaciones

Ubicación: `app/Http/Controllers/`. Controlador de referencia a imitar: `app/Http/Controllers/ProductController.php`.

## Estructura de un controlador de recurso (CRUD estándar)

```php
public function index(Request $request)
{
    $items = QueryBuilder::for(Model::class)
        ->with('relation')
        ->allowedFilters([
            AllowedFilter::callback('buscar', function ($query, $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%");
                });
            }),
            AllowedFilter::exact('currentCategory', 'model_category_id'),
        ])
        ->allowedSorts(['id', 'name'])
        ->defaultSort('name')
        ->paginate(10)
        ->appends($request->query());

    return view('modelos.index', compact('items'));
}

public function store(StoreModelRequest $request)
{
    $data = $request->validated();

    $model = Model::create($data);

    return redirect()->route('modelos.index')->with('success', 'Registro creado exitosamente.');
}

public function update(UpdateModelRequest $request, Model $model)
{
    $model->update($request->validated());

    return redirect()->route('modelos.index')->with('success', 'Registro actualizado exitosamente.');
}

public function destroy(Model $model)
{
    $model->delete();

    return redirect()->route('modelos.index')->with('success', 'Registro eliminado exitosamente.');
}
```

## Reglas del proyecto

- **Listados**: usar `Spatie\QueryBuilder\QueryBuilder` con `AllowedFilter`, no construir el filtrado a mano con `if ($request->has(...))`.
  El filtro de texto libre casi siempre se llama `buscar` (por convención en este proyecto, no `search` ni `q`).
  Paginación estándar: `paginate(10)`, con `->appends($request->query())` para conservar filtros al paginar.
- **Route Model Binding** implícito en `show`/`edit`/`update`/`destroy` (tipar el parámetro con el modelo, ej. `Product $product`),
  no buscar manualmente con `Model::findOrFail($id)`.
- **Mensajes flash**: `redirect()->route(...)->with('success', 'Mensaje en español, en pasado/terminado.')`. Los errores de
  validación los maneja el `FormRequest` automáticamente (no captures excepciones de validación a mano).
- **Transacciones**: cuando una acción toca más de una tabla relacionada (ej. crear un producto + su movimiento de inventario
  inicial, o una venta + sus items + el stock), envuelve la lógica en `DB::transaction(function () { ... });` — ver
  `ProductController::store()` como ejemplo real.
- **Archivos**: subir con `$request->file('campo')->store('carpeta', 'uploads')` (el disco se llama `uploads`, no `public`).
  Al reemplazar/borrar, usar `Storage::disk('uploads')->delete($path)`.
- **Excel** (paquete `maatwebsite/excel`): exportar con `Excel::download(new XxxExport($data), $fileName)` (clases en
  `app/Exports/`), importar con `Excel::import(new XxxImport(...), $request->file('file'))` (clases en `app/Imports/`),
  usando un `Request` dedicado tipo `ImportXxxRequest`.
- **Autorización**: hoy el proyecto autoriza casi todo a nivel de **rutas** (`middleware(['permission:ver products'])` en
  `routes/web.php`), no dentro del controlador ni con Policies activas (ver skill `laravel-policy` antes de agregar
  `$this->authorize(...)` a un controlador — hay que registrar la policy primero, `AuthServiceProvider` está vacío hoy).
  Si necesitas una verificación fina dentro de una acción (ej. "solo el rol admin puede anular"), usa
  `abort_unless(auth()->user()->can('ventas.anular'), 403);` en línea con los nombres de permisos existentes
  (ver skill `spatie-roles-permissions`).
- No introduzcas capas de Service/Repository/Action nuevas salvo que el usuario lo pida explícitamente — la lógica vive en
  el controlador (o en el modelo si es puramente de datos propios del modelo).
