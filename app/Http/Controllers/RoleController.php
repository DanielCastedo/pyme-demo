<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        // Quien no es superadmin no ve el rol superadmin
        $roles = QueryBuilder::for(Role::query()->when(! $this->isSuperAdmin(), fn ($q) => $q->where('name', '!=', 'superadmin')))
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('id')
            ->paginate(10)
            ->appends($request->query());

        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();

        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        // SOLUCIÓN: Agregamos explícitamente el guard_name 'web' aquí
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        // Si se enviaron permisos, se los asignamos al rol
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Rol creado exitosamente.');
    }

    public function show(Role $role)
    {
        $this->hideSuperadminRole($role);

        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        $this->hideSuperadminRole($role);

        return view('roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $this->hideSuperadminRole($role);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,'.$role->id,
        ]);

        // Los roles base se referencian por nombre en rutas y permisos: no se renombran
        if (in_array($role->name, ['superadmin', 'admin', 'operador'], true) && $request->name !== $role->name) {
            return redirect()->route('roles.index')
                ->with('error', 'No se pueden renombrar los roles base del sistema.');
        }

        // En el update no hace falta cambiar el guard_name porque ya existe
        $role->update(['name' => $request->name]);

        return redirect()->route('roles.index')
            ->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy(Role $role)
    {
        $this->hideSuperadminRole($role);

        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Solo el superadmin puede eliminar roles.');

        if (in_array($role->name, ['superadmin', 'admin', 'operador'], true)) {
            return redirect()->route('roles.index')
                ->with('error', 'No se pueden eliminar los roles base del sistema.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Rol eliminado exitosamente.');
    }

    private function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('superadmin') ?? false;
    }

    /** El rol superadmin no existe para quien no es superadmin. */
    private function hideSuperadminRole(Role $role): void
    {
        abort_if($role->name === 'superadmin' && ! $this->isSuperAdmin(), 404);
    }
}
