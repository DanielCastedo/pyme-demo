<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $currentUser = auth()->user();
        $isSuperAdmin = $currentUser ? $currentUser->hasRole('superadmin') : false;

        $query = User::query()->with(['roles', 'branch']);

        // El admin NO puede listar al superadmin; solo ve los demás roles
        if (!$isSuperAdmin) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'superadmin'));
        }

        $usuarios = QueryBuilder::for($query)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('id')
            ->paginate(10)
            ->appends($request->query());

        // Si no es superadmin, tampoco puede ver ni asignar el rol superadmin
        $roles = Role::query()
            ->when(!$isSuperAdmin, fn ($q) => $q->where('name', '!=', 'superadmin'))
            ->get();

        $branches = Branch::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios', 'roles', 'isSuperAdmin', 'branches'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;

        $roles = Role::query()
            ->when(!$isSuperAdmin, fn ($q) => $q->where('name', '!=', 'superadmin'))
            ->get();

        $branches = Branch::orderBy('name')->get();

        return view('usuarios.create', compact('roles', 'isSuperAdmin', 'branches'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'branche_id' => 'required|exists:branches,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
        ], [
            'branche_id.required' => 'La sucursal es obligatoria.',
            'branche_id.exists' => 'La sucursal seleccionada no existe.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.min' => 'Debes asignar al menos un rol.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ]);

        if (!$isSuperAdmin && in_array('superadmin', (array) $request->roles, true)) {
            abort(403, 'No puedes asignar el rol superadmin.');
        }

        $usuario = new User;
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->password = Hash::make($request->password);
        $usuario->branche_id = $request->branche_id;

        // Si es superadmin, puede designar al admin para términos
        if ($isSuperAdmin && $request->boolean('is_designated_admin')) {
            $usuario->is_designated_admin = true;
        }

        $usuario->save();

        // Asignar los roles seleccionados al usuario
        $usuario->assignRole($request->roles);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado y roles asignados correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $usuario)
    {
        if ($usuario->hasRole('superadmin') && !auth()->user()?->hasRole('superadmin')) {
            abort(403, 'No tienes permiso para ver a un superadmin.');
        }

        return view('usuarios.show', compact('usuario'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $usuario)
    {
        $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;

        if ($usuario->hasRole('superadmin') && !$isSuperAdmin) {
            abort(403, 'No tienes permiso para ver o editar a un superadmin.');
        }

        $roles = Role::query()
            ->when(!$isSuperAdmin, fn ($q) => $q->where('name', '!=', 'superadmin'))
            ->get();

        $userRoles = $usuario->roles->pluck('name')->toArray();

        $branches = Branch::orderBy('name')->get();

        return view('usuarios.edit', compact('usuario', 'roles', 'userRoles', 'isSuperAdmin', 'branches'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $usuario)
    {
        $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;

        if ($usuario->hasRole('superadmin') && !$isSuperAdmin) {
            abort(403, 'No tienes permiso para editar a un superadmin.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$usuario->id,
            'password' => 'nullable|string|min:8',
            'branche_id' => 'required|exists:branches,id',
            'roles' => 'required|array|min:1',
            'roles.*' => 'exists:roles,name',
        ], [
            'branche_id.required' => 'La sucursal es obligatoria.',
            'branche_id.exists' => 'La sucursal seleccionada no existe.',
            'roles.required' => 'Debes asignar al menos un rol.',
            'roles.min' => 'Debes asignar al menos un rol.',
            'roles.*.exists' => 'Uno de los roles seleccionados no existe.',
        ]);

        if (!$isSuperAdmin && in_array('superadmin', (array) $request->roles, true)) {
            abort(403, 'No puedes asignar el rol superadmin.');
        }

        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->branche_id = $request->branche_id;

        if ($request->filled('password')) {
            $usuario->password = Hash::make($request->password);
        }

        // Si es superadmin, puede gestionar la designación de términos y condiciones
        if ($isSuperAdmin) {
            $usuario->is_designated_admin = $request->boolean('is_designated_admin');
            if ($request->boolean('reset_terms')) {
                $usuario->terms_accepted_at = null;
                $usuario->terms_ip = null;
            }
        }

        $usuario->save();

        $usuario->syncRoles($request->roles);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $isSuperAdmin = auth()->user()?->hasRole('superadmin') ?? false;

        if ($usuario->hasRole('superadmin') && !$isSuperAdmin) {
            abort(403, 'No tienes permiso para eliminar a un superadmin.');
        }

        if ($usuario->hasRole('superadmin')) {
            abort(403, 'No se puede eliminar a un usuario con rol superadmin.');
        }

        if ($usuario->id === auth()->id()) {
            abort(403, 'No puedes eliminar tu propia cuenta.');
        }

        // Eliminación lógica (SoftDeletes): el usuario pierde el acceso, pero sus ventas, compras,
        // movimientos, etc. conservan quién los hizo.
        $usuario->delete();

        // Cierra sus sesiones abiertas y revoca sus tokens
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $usuario->id)->delete();
        $usuario->tokens()->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente. Su historial se conserva.');
    }
}
