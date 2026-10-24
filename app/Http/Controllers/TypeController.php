<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTypeRequest;
use App\Http\Requests\UpdateTypeRequest;
use App\Models\Type;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Type::class, 'type');
    }

    public function index(Request $request)
    {
        $types = QueryBuilder::for(Type::class)
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

        return view('types.index', compact('types'));
    }

    public function store(StoreTypeRequest $request)
    {
        Type::create($request->validated());

        return redirect()->route('types.index')
            ->with('success', 'Tipo registrado exitosamente.');
    }

    public function show(Type $type)
    {
        return view('types.show', compact('type'));
    }

    public function edit(Type $type)
    {
        return view('types.edit', compact('type'));
    }

    public function update(UpdateTypeRequest $request, Type $type)
    {
        $type->update($request->validated());

        return redirect()->route('types.index')
            ->with('success', 'Tipo actualizado exitosamente.');
    }

    public function destroy(Type $type)
    {
        $type->delete();

        return redirect()->route('types.index')
            ->with('success', 'Tipo eliminado exitosamente.');
    }
}
