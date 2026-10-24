<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    public function optimize(Request $request)
    {
        if (! $this->hasValidToken($request)) {
            abort(403, 'Token inválido.');
        }

        Artisan::call('optimize');

        return response()->json([
            'status' => 'ok',
            'message' => 'php artisan optimize ejecutado correctamente.',
            'output' => trim(Artisan::output()),
            'timestamp' => now()->toDateTimeString(),
        ]);
    }

    private function hasValidToken(Request $request): bool
    {
        $token = $request->query('token') ?? $request->header('X-Deploy-Token');
        $expected = config('app.deploy_secret');

        return $expected && $token && hash_equals($expected, $token);
    }
}
