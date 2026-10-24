<?php

namespace App\Http\Controllers;

use App\Services\PosFormData;
use Illuminate\Http\Request;

/**
 * Buscador de productos del POS (parcial sales.partials.pos, compartido por Ventas, Pedidos, Consignaciones
 * y Proformas): responde de a poco (como máximo PosFormData::SEARCH_LIMIT a la vez) en vez de mandar el
 * catálogo entero al navegador. No pide un permiso propio: las pantallas que lo usan ya están protegidas
 * cada una con el suyo (ver sales / ver orders / crear consignments / ver proformas); el costo de cada
 * producto sí queda condicionado acá mismo a "ventas.editar-precio", igual que en esas pantallas.
 */
class PosSearchController extends Controller
{
    public function search(Request $request, PosFormData $data)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:255',
            'ids' => 'nullable|string|max:2000',
        ]);

        $ids = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'units' => $data->searchUnits([
                'q' => $validated['q'] ?? null,
                'code' => $validated['code'] ?? null,
                'ids' => $ids,
            ], $request->user()->can('ventas.editar-precio')),
        ]);
    }

    /**
     * Buscador de clientes del POS: mismo motivo que search() de arriba, pero para la cartera de
     * clientes (antes se mandaba entera en cada apertura de la pantalla).
     */
    public function searchCustomers(Request $request, PosFormData $data)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'ids' => 'nullable|string|max:2000',
        ]);

        $ids = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        return response()->json([
            'customers' => $data->searchCustomers([
                'q' => $validated['q'] ?? null,
                'ids' => $ids,
            ]),
        ]);
    }
}
