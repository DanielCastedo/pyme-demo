<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Cuentas por cobrar: ventas a crédito vigentes con saldo pendiente.
 */
class ReceivableController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $base = fn () => Order::query()
            ->where('payment_type', Order::PAYMENT_CREDIT)
            ->where('status', Order::STATUS_COMPLETED)
            ->whereColumn('paid_amount', '<', 'amount');

        $receivables = QueryBuilder::for($base())
            ->with('customer')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('id', ltrim($value, '#'))
                            ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$value}%")
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

        return view('receivables.index', compact('receivables', 'totalOwed', 'totalOverdue'));
    }
}
