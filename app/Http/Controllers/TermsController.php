<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TermsController extends Controller
{
    /**
     * Muestra la vista de Términos y Condiciones al final de los módulos.
     * Accesible por superadmin, admin y usuario/administradora designado/a.
     */
    public function index()
    {
        $user = auth()->user();

        // Solo superadmin, admin o el administrador designado pueden ver este apartado
        if (!$user->hasRole('superadmin') && !$user->hasRole('admin') && !$user->is_designated_admin) {
            abort(403, 'No tienes permiso para ver los Términos y Condiciones.');
        }

        $company = Company::first();
        $designatedAdmin = User::where('is_designated_admin', true)->first();

        return view('terms.index', compact('user', 'company', 'designatedAdmin'));
    }

    /**
     * Pantalla obligatoria de lectura y aceptación para el admin designado al ingresar.
     */
    public function pending()
    {
        $user = auth()->user();

        if (!$user->mustAcceptTerms()) {
            return redirect()->route('dashboard');
        }

        $company = Company::first();

        return view('terms.pending', compact('user', 'company'));
    }

    /**
     * Procesa la aceptación de los términos.
     */
    public function accept(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'accept_terms' => 'required|accepted',
        ], [
            'accept_terms.required' => 'Debes marcar la casilla para confirmar que aceptas los Términos y Condiciones.',
            'accept_terms.accepted' => 'Debes marcar la casilla para confirmar que aceptas los Términos y Condiciones.',
        ]);

        $user->update([
            'terms_accepted_at' => now(),
            'terms_ip' => $request->ip(),
            'terms_version' => '1.0',
        ]);

        activity('terminos')
            ->causedBy($user)
            ->log("El usuario {$user->name} ({$user->email}) aceptó los Términos y Condiciones de Licencia del Sistema (IP: {$request->ip()}). Estado: Confirmado.");

        return redirect()->route('dashboard')->with('success', 'Has aceptado los Términos y Condiciones del Servicio. Tu estado ha pasado a Confirmado.');
    }

    /**
     * Procesa el rechazo de los términos y cierra la sesión del usuario.
     */
    public function reject(Request $request)
    {
        $user = $request->user();

        activity('terminos')
            ->causedBy($user)
            ->log("El usuario {$user->name} ({$user->email}) rechazó los Términos y Condiciones de Licencia del Sistema.");

        Auth::guard('web')->logout();
        app('auth')->forgetGuards();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Has rechazado los Términos y Condiciones. Debes aceptarlos para poder ingresar al sistema.');
    }
}
