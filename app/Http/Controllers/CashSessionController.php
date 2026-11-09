<?php

namespace App\Http\Controllers;

use App\Models\Cashbox;
use App\Models\CashSession;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Http\Request;

/**
 * "Mi caja": apertura (con fondo inicial) y cierre (con arqueo) de caja por usuario, e historial de turnos.
 * Cada usuario maneja su propio turno; quien puede administrar cajas (ver cashboxes) ve todos y puede cerrar uno ajeno
 * (por ejemplo, si el vendedor se fue sin cerrar).
 */
class CashSessionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canSeeAll = $user->can('ver cashboxes');

        $current = CashSession::openFor($user->id)?->load('cashbox.branch');
        $summary = $current?->summary();

        // Cajas donde puede abrir: las de su sucursal (o todas, si no tiene cajas en la suya).
        $cashboxes = Cashbox::where('branche_id', $user->branche_id)->orderBy('name')->get();
        if ($cashboxes->isEmpty()) {
            $cashboxes = Cashbox::orderBy('name')->get();
        }

        $filters = $request->validate([
            'user' => 'nullable|integer|exists:users,id',
            'cashbox' => 'nullable|integer|exists:cashboxes,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $history = CashSession::with('cashbox', 'user')
            ->when(! $canSeeAll, fn ($q) => $q->where('user_id', $user->id))
            ->when($canSeeAll && ! empty($filters['user']), fn ($q) => $q->where('user_id', $filters['user']))
            ->when(! empty($filters['cashbox']), fn ($q) => $q->where('cashbox_id', $filters['cashbox']))
            ->when(! empty($filters['from']), fn ($q) => $q->whereDate('opened_at', '>=', $filters['from']))
            ->when(! empty($filters['to']), fn ($q) => $q->whereDate('opened_at', '<=', $filters['to']))
            ->latest('opened_at')->latest('id')
            ->paginate(15)
            ->withQueryString();

        $users = $canSeeAll ? User::whereIn('id', CashSession::select('user_id'))->orderBy('name')->get(['id', 'name']) : collect();
        $allCashboxes = Cashbox::orderBy('name')->get(['id', 'name']);
        $defaultOpening = config('caja.default_opening_amount');

        return view('cash-sessions.index', compact('current', 'summary', 'cashboxes', 'history', 'canSeeAll', 'users', 'allCashboxes', 'defaultOpening'));
    }

    public function store(Request $request, CashSessionService $service)
    {
        $data = $request->validate([
            'cashbox_id' => 'required|exists:cashboxes,id',
            'opening_amount' => 'required|numeric|min:0',
            'opening_note' => 'nullable|string|max:255',
        ], [
            'cashbox_id.required' => 'Elige la caja que vas a abrir.',
            'opening_amount.required' => 'Indica el fondo inicial (puede ser 0).',
            'opening_amount.min' => 'El fondo inicial no puede ser negativo.',
        ]);

        try {
            $service->open($request->user(), Cashbox::findOrFail($data['cashbox_id']), $data['opening_amount'], $data['opening_note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cash-sessions.index')
            ->with('success', 'Caja abierta con un fondo de '.number_format((float) $data['opening_amount'], 2).'. Ya puedes cobrar.');
    }

    public function close(Request $request, CashSession $cashSession, CashSessionService $service)
    {
        $this->authorizeSession($request->user(), $cashSession);

        $data = $request->validate([
            'counted_cash' => 'required|numeric|min:0',
            'closing_note' => 'nullable|string|max:255',
        ], [
            'counted_cash.required' => 'Indica cuánto efectivo contaste.',
            'counted_cash.min' => 'El efectivo contado no puede ser negativo.',
        ]);

        try {
            $service->close($cashSession, $data['counted_cash'], $request->user(), $data['closing_note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('cash-sessions.show', $cashSession)
            ->with('success', 'Caja cerrada: '.$cashSession->fresh()->difference_label.'.');
    }

    public function show(Request $request, CashSession $cashSession)
    {
        $this->authorizeSession($request->user(), $cashSession);

        $cashSession->load('cashbox.branch', 'user', 'closer');
        $summary = $cashSession->summary();
        $transactions = $cashSession->transactions()->with('type', 'paymentMethod')->latest('date')->latest('id')->get();

        return view('cash-sessions.show', compact('cashSession', 'summary', 'transactions'));
    }

    /** Cada usuario ve y cierra su propio turno; quien administra cajas, cualquiera. */
    private function authorizeSession(User $user, CashSession $session): void
    {
        abort_unless((int) $session->user_id === (int) $user->id || $user->can('ver cashboxes'), 403);
    }
}
