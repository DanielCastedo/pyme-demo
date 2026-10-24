<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Warehouse::class, 'warehouse');
    }

    public function index(Request $request)
    {
        $warehouses = QueryBuilder::for(Warehouse::class)
            ->with('branch')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('code', 'like', "%{$value}%");
                    });
                }),
            ])
            ->allowedSorts(['id', 'name', 'code'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        $branches = Branch::orderBy('name')->get();

        return view('warehouses.index', compact('warehouses', 'branches'));
    }

    public function store(StoreWarehouseRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $warehouse = Warehouse::create($data);

        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén registrado exitosamente.')
            ->with('default_warehouse', $this->defaultWarehousePayload($request, $warehouse));
    }

    public function show(Warehouse $warehouse)
    {
        $warehouse->load('branch');

        // Un almacén puede tener miles de productos: se muestran por páginas.
        $products = $warehouse->products()->orderBy('products.name')->paginate(30)->withQueryString();

        return view('warehouses.show', compact('warehouse', 'products'));
    }

    public function edit(Warehouse $warehouse)
    {
        $branches = Branch::orderBy('name')->get();

        return view('warehouses.edit', compact('warehouse', 'branches'));
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $warehouse->update($data);

        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén actualizado exitosamente.')
            ->with('default_warehouse', $this->defaultWarehousePayload($request, $warehouse));
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return redirect()->route('warehouses.index')
            ->with('success', 'Almacén eliminado exitosamente.');
    }

    /**
     * "Usar como almacén predeterminado" no se guarda en la base de datos: solo se le avisa una vez,
     * por sesión, a la vista de listado para que lo escriba en el localStorage del navegador (ver
     * warehouses/index.blade.php). Así cada dispositivo/caja puede tener su propio almacén por defecto.
     *
     * @return array{id: int, name: string, branche_id: int}|null
     */
    private function defaultWarehousePayload(Request $request, Warehouse $warehouse): ?array
    {
        if (! $request->boolean('default_warehouse')) {
            return null;
        }

        return ['id' => $warehouse->id, 'name' => $warehouse->name, 'branche_id' => $warehouse->branche_id];
    }
}
