<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Cashbox;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Gastos del negocio: cada uno saca dinero de una caja (ver App\Services\ExpenseService).
 */
class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Expense::class, 'expense');
    }

    public function index(Request $request)
    {
        $expenses = QueryBuilder::for(Expense::class)
            ->with('category', 'supplier')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('id', ltrim($value, '#'))
                            ->orWhere('concept', 'like', "%{$value}%")
                            ->orWhere('receipt_number', 'like', "%{$value}%")
                            ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$value}%"))
                            ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentStatus', 'status'),
                AllowedFilter::exact('categoria', 'expense_category_id'),
            ])
            ->allowedSorts(['id', 'date', 'amount'])
            ->defaultSort('-id')
            ->paginate(10)
            ->appends($request->query());

        // Total de lo que se gastó (sin anulados) según los filtros aplicados.
        $total = QueryBuilder::for(Expense::class)
            ->where('status', Expense::STATUS_PAID)
            ->allowedFilters([AllowedFilter::exact('categoria', 'expense_category_id')])
            ->sum('amount');

        return view('expenses.index', compact('expenses', 'total'));
    }

    public function create()
    {
        return view('expenses.create', $this->formData());
    }

    public function store(StoreExpenseRequest $request, ExpenseService $service)
    {
        try {
            $expense = $service->register($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('expenses.show', $expense)
            ->with('success', "Gasto #{$expense->id} registrado por {$expense->amount_label}: ya se descontó de la caja.");
    }

    public function show(Expense $expense)
    {
        $expense->load('category', 'supplier', 'branch', 'user', 'transaction.cashbox', 'transaction.paymentMethod');

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        $expense->load('transaction');

        return view('expenses.edit', $this->formData() + ['expense' => $expense]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense, ExpenseService $service)
    {
        try {
            $service->update($expense, $request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('expenses.show', $expense)->with('success', 'Gasto actualizado: la caja se ajustó al nuevo monto.');
    }

    /**
     * Eliminar un gasto vigente lo anula (el dinero vuelve a la caja). Si ya estaba anulado, se borra el registro.
     */
    public function destroy(Expense $expense, ExpenseService $service)
    {
        if ($expense->isCanceled()) {
            $expense->delete();

            return redirect()->route('expenses.index')->with('success', 'Gasto eliminado.');
        }

        try {
            $service->cancel($expense);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('expenses.index')->with('success', 'Gasto anulado: el dinero volvió a la caja.');
    }

    private function formData(): array
    {
        return [
            'categories' => ExpenseCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::orderByDesc('is_cash')->orderBy('name')->get(['id', 'name']),
            'cashboxes' => Cashbox::with('branch:id,name')->orderBy('name')->get(),
            'canCreateCategory' => auth()->user()->can('create', ExpenseCategory::class),
        ];
    }

    public function downloadPdf(Expense $expense)
    {
        $this->authorize('view', $expense);
        $expense->load('category', 'supplier', 'branch', 'user', 'transaction.cashbox', 'transaction.paymentMethod');
        $company = \App\Models\Company::first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('expenses.pdf', compact('expense', 'company'));
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Gasto-{$expense->id}.pdf");
    }
}
