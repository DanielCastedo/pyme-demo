<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Cuentas por pagar: compras a crédito vigentes con saldo pendiente a un proveedor.
 */
class PayableController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Purchase::class);

        $base = fn () => Purchase::query()
            ->where('payment_type', Purchase::PAYMENT_CREDIT)
            ->where('status', Purchase::STATUS_RECEIVED)
            ->whereColumn('paid_amount', '<', 'amount');

        $payables = QueryBuilder::for($base())
            ->with('supplier')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('id', ltrim($value, '#'))
                            ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$value}%")
                                ->orWhere('document', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::callback('vencidas', function ($query, $value) {
                    if ($value) {
                        $query->whereDate('due_date', '<', now()->toDateString());
                    }
                }),
            ])
            ->allowedSorts(['id', 'due_date', 'amount'])
            ->defaultSort('due_date')
            ->paginate(10)
            ->appends($request->query());

        $totalOwed = (float) $base()->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as owed')->value('owed');
        $totalOverdue = (float) $base()->whereDate('due_date', '<', now()->toDateString())->selectRaw('COALESCE(SUM(amount - paid_amount), 0) as owed')->value('owed');

        return view('payables.index', compact('payables', 'totalOwed', 'totalOverdue'));
    }
}
