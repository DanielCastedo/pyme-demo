<?php

namespace App\Http\Controllers;

// IMPORTANTE: Usamos los modelos de Spatie, NO el modelo RolePermisos
use App\Support\PermissionGroups;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermisosController extends Controller
{
    /**
     * Muestra una lista de los Roles y los permisos que tienen asignados.
     */
    public function index()
    {
        // Traemos los roles junto con sus permisos para evitar el problema de N+1 consultas
        // Quien no es superadmin no ve el rol superadmin
        $roles = Role::with('permissions')
            ->when(! auth()->user()?->hasRole('superadmin'), fn ($q) => $q->where('name', '!=', 'superadmin'))
            ->paginate(6);

        return view('rolepermisos.index', compact('roles'));
    }

    /**
     * Muestra el formulario para asignar/quitar permisos a un Rol específico.
     * Nota: Cambiamos la inyección a (string $id) para buscar el Rol.
     */
    public function edit(string $id)
    {
        $role = Role::findOrFail($id);
        $this->hideSuperadminRole($role);

        // Nombres de los permisos que ya tiene el rol
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        $groups = PermissionGroups::group(Permission::orderBy('name')->pluck('name'));

        // Los permisos de Seguridad, Configuración y Sistema son solo del superadmin: los demás roles no los ven
        if ($role->name !== 'superadmin') {
            $groups = array_diff_key($groups, array_flip(PermissionGroups::SUPERADMIN_AREAS));
        }

        return view('rolepermisos.edit', compact('role', 'groups', 'rolePermissions'));
    }

    /**
     * Actualiza los permisos del Rol en la base de datos.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $role = Role::findOrFail($id);
        $this->hideSuperadminRole($role);

        $permissions = $request->permissions ?? [];

        // Los roles que no son superadmin no ven ni administran los permisos reservados al superadmin:
        // se ignora lo que llegue de esas áreas y se conservan los que el rol ya tenga.
        if ($role->name !== 'superadmin') {
            $reserved = collect(PermissionGroups::group(Permission::pluck('name')))
                ->only(PermissionGroups::SUPERADMIN_AREAS)
                ->flatten(1)
                ->flatMap(fn ($module) => array_keys($module['permissions']))
                ->all();

            $permissions = array_merge(
                array_diff($permissions, $reserved),
                array_intersect($role->permissions->pluck('name')->all(), $reserved)
            );
        }

        // Sincronizamos los permisos usando el método mágico de Spatie
        // Si no envía nada, le pasamos un array vacío para quitarle todos los permisos
        $role->syncPermissions($permissions);

        return redirect()->route('role-permisos.index')
            ->with('success', 'Permisos actualizados correctamente para el rol: '.$role->name);
    }

    /** El rol superadmin no existe para quien no es superadmin. */
    private function hideSuperadminRole(Role $role): void
    {
        abort_if($role->name === 'superadmin' && ! auth()->user()?->hasRole('superadmin'), 404);
    }

    // Los métodos create, store, show y destroy no son necesarios aquí
    // porque los roles y permisos se crean/eliminan en sus propios controladores.
}
