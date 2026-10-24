<?php

namespace App\Http\Controllers;

// Importamos SOLO el modelo de SPATIE (es obligatorio usar este)
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PermisosController extends Controller
{
    public function index(Request $request)
    {
        // Cambiamos la variable a $permisos
        $permisos = QueryBuilder::for(Permission::class)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->orderBy('name')
            ->paginate(10)
            ->appends($request->query());

        return view('permisos.index', compact('permisos'));
    }

    public function create()
    {
        return view('permisos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        // Forzamos explícitamente a que el guard sea 'web'
        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web', // <-- Agrega esta línea
        ]);

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso creado exitosamente.');
    }

    // Usamos el modelo Permission, pero la variable en español $permiso
    public function show(Permission $permiso)
    {
        return view('permisos.show', compact('permiso'));
    }

    public function edit(Permission $permiso)
    {
        return view('permisos.edit', compact('permiso'));
    }

    public function update(Request $request, Permission $permiso)
    {
        // OJO: La regla unique debe buscar en la tabla 'permissions'
        $request->validate([
            'name' => 'required|unique:permissions,name,'.$permiso->id,
        ]);

        $permiso->update(['name' => $request->name]);

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso actualizado exitosamente.');
    }

    public function destroy(Permission $permiso)
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Solo el superadmin puede eliminar permisos.');

        $permiso->delete();

        return redirect()->route('permisos.index')
            ->with('success', 'Permiso eliminado exitosamente.');
    }
}
