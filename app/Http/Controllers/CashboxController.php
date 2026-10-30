<?php

namespace App\Http\Controllers;

use App\Support\Bc;
use App\Exports\CashboxTotalsByTypeExport;
use App\Http\Requests\StoreCashboxRequest;
use App\Http\Requests\UpdateCashboxRequest;
use App\Models\Branch;
use App\Models\Cashbox;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * El efectivo (`cash`) y el saldo (`balance`) de una caja NO se editan a mano: solo
 * cambian con transacciones. Por eso el CRUD gestiona únicamente nombre y sucursal.
 */
class CashboxController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Cashbox::class, 'cashbox');
    }

    public function index(Request $request)
    {
        $cashboxes = QueryBuilder::for(Cashbox::query()->select('cashboxes.*')->withQrTotal())
            ->with('branch')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhereHas('branch', fn ($b) => $b->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentBranch', 'branche_id'),
            ])
            ->allowedSorts(['id', 'name', 'cash', 'balance'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        $branches = Branch::orderBy('name')->get();

        return view('cashboxes.index', compact('cashboxes', 'branches'));
    }

    public function store(StoreCashboxRequest $request)
    {
        Cashbox::create($request->validated());

        return redirect()->route('cashboxes.index')
            ->with('success', 'Caja registrada exitosamente.');
    }

    public function show(Request $request, Cashbox $cashbox)
    {
        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $cashbox->load('branch');

        $totals = $cashbox->totalsByType($request->query('from'), $request->query('to'));

        return view('cashboxes.show', compact('cashbox', 'totals'));
    }

    /** Exporta a Excel los totales por tipo de transacción (solo vigentes) del período filtrado. */
    public function exportTotals(Request $request, Cashbox $cashbox)
    {
        $this->authorize('view', $cashbox);

        $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->query('from');
        $to = $request->query('to');

        $fileName = 'caja-'.str($cashbox->name)->slug().'-totales'
            .($from ? '-desde-'.$from : '')
            .($to ? '-hasta-'.$to : '')
            .'.xlsx';

        return Excel::download(new CashboxTotalsByTypeExport($cashbox, $from, $to), $fileName);
    }

    public function edit(Cashbox $cashbox)
    {
        $cashbox->load('branch');
        $branches = Branch::orderBy('name')->get();

        return view('cashboxes.edit', compact('cashbox', 'branches'));
    }

    public function update(UpdateCashboxRequest $request, Cashbox $cashbox)
    {
        $data = $request->validated();

        // Las transacciones guardan la sucursal de la caja: moverla rompería ese historial.
        if ((int) $data['branche_id'] !== (int) $cashbox->branche_id && $cashbox->transactions()->exists()) {
            return back()->withInput()->with('error', 'No se puede cambiar la sucursal de una caja que ya tiene transacciones.');
        }

        $cashbox->update($data);

        return redirect()->route('cashboxes.index')
            ->with('success', 'Caja actualizada exitosamente.');
    }

    public function destroy(Cashbox $cashbox)
    {
        if ($cashbox->transactions()->exists()) {
            return redirect()->route('cashboxes.index')
                ->with('error', 'No se puede eliminar la caja: tiene transacciones registradas.');
        }

        if (! Bc::isZero($cashbox->balance) || ! Bc::isZero($cashbox->cash)) {
            return redirect()->route('cashboxes.index')
                ->with('error', 'No se puede eliminar la caja: todavía tiene dinero.');
        }

        $cashbox->delete();

        return redirect()->route('cashboxes.index')
            ->with('success', 'Caja eliminada exitosamente.');
    }
}
