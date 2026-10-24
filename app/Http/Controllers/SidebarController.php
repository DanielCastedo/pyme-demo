<?php

namespace App\Http\Controllers;

use App\Models\Sidebar;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SidebarController extends Controller
{
    public function index(Request $request)
    {
        $menus = QueryBuilder::for(Sidebar::class)
            ->with('parent')
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'nombre'),
            ])
            ->allowedSorts(['orden', 'nombre', 'rol_requerido'])
            ->defaultSort('orden')
            ->paginate(10)
            ->appends($request->query());

        $menusPadre = Sidebar::whereNull('parent_id')->get();
        $roles = cache()->remember('roles.all', 3600, fn () => Role::all());
        $permisos = cache()->remember('permisos.all', 3600, fn () => Permission::all());

        return view('sidebars.index', compact('menus', 'menusPadre', 'roles', 'permisos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'ruta' => 'nullable|string|max:255',
            'orden' => 'required|integer',
            'parent_id' => 'nullable|exists:sidebars,id',
        ]);

        Sidebar::create($request->all());

        return redirect()->route('sidebars.index')->with('success', 'Menú creado correctamente.');
    }

    public function edit(Sidebar $sidebar)
    {
        $menusPadre = Sidebar::whereNull('parent_id')->where('id', '!=', $sidebar->id)->get();
        $roles = Role::all();
        $permisos = Permission::all();

        return view('sidebars.edit', compact('sidebar', 'menusPadre', 'roles', 'permisos'));
    }

    public function update(Request $request, Sidebar $sidebar)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'ruta' => 'nullable|string|max:255',
            'orden' => 'required|integer',
            'parent_id' => 'nullable|exists:sidebars,id',
            'rol_requerido' => 'nullable|string',
            'permiso_requerido' => 'nullable|string',
        ]);

        $sidebar->update($request->all());

        return redirect()->route('sidebars.index')->with('success', 'Menú actualizado correctamente.');
    }

    public function destroy(Sidebar $sidebar)
    {
        $sidebar->delete();

        return redirect()->route('sidebars.index')->with('success', 'Menú eliminado correctamente.');
    }
}
