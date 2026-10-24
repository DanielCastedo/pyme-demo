<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Catálogo de unidades de medida (Unidad, Caja, Docena, Kilogramo...). Las presentaciones de cada producto
 * (ProductUnitController) eligen una de estas unidades.
 */
class UnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Unit::class, 'unit');
    }

    public function index(Request $request)
    {
        $units = QueryBuilder::for(Unit::class)
            ->withCount('productUnits')
            ->allowedFilters([
                AllowedFilter::callback('buscar', fn ($query, $value) => $query->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%{$value}%")->orWhere('abbreviation', 'like', "%{$value}%");
                })),
                AllowedFilter::exact('currentStatus', 'is_active'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('units.index', compact('units'));
    }

    public function store(StoreUnitRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $unit = Unit::create($data);

        // Alta rápida desde otras pantallas (modal): devuelve la unidad para seleccionarla al instante.
        if ($request->expectsJson()) {
            return response()->json($unit->only('id', 'name', 'abbreviation'), 201);
        }

        return redirect()->route('units.index')->with('success', 'Unidad registrada exitosamente.');
    }

    public function edit(Unit $unit)
    {
        return view('units.edit', compact('unit'));
    }

    public function update(StoreUnitRequest $request, Unit $unit)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $unit->update($data);

        return redirect()->route('units.index')
            ->with('success', 'Unidad actualizada exitosamente. Las presentaciones de productos que la usan tomaron el nuevo nombre.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->productUnits()->exists()) {
            return redirect()->route('units.index')
                ->with('error', 'No se puede eliminar la unidad: hay productos que la usan. Puedes desactivarla en su lugar.');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'Unidad eliminada exitosamente.');
    }
}
