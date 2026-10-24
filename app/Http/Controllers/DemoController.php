<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Restablece la base de datos de demostración desde el login: borra todas las tablas, las vuelve a crear
 * y corre los seeders, así el inventario, las ventas y la caja quedan como en la instalación inicial.
 * Solo existe con DEMO_MODE=true (ver config/demo.php).
 */
class DemoController extends Controller
{
    public function reset(): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        // migrate:fresh + seeders puede tardar varios segundos en MySQL.
        @set_time_limit(300);

        try {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
            Artisan::call('permission:cache-reset');
        } catch (\Throwable $e) {
            Log::error('No se pudieron restablecer los datos de demostración', ['exception' => $e]);

            return redirect()->route('login')->withErrors(['demo' => 'No se pudieron restablecer los datos: '.$e->getMessage()]);
        }

        return redirect()->route('login')->with('status', 'Datos de demostración restablecidos: el inventario y los demás registros volvieron a su estado inicial.');
    }
}
