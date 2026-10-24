<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Proveedores: a quienes se les compra mercadería (ver PurchaseController).
 */
class SupplierController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Supplier::class, 'supplier');
    }

    public function index(Request $request)
    {
        $suppliers = QueryBuilder::for(Supplier::class)
            ->withCount('purchases')
            ->allowedFilters([
                AllowedFilter::scope('buscar', 'search'),
                AllowedFilter::exact('currentStatus', 'is_active'),
            ])
            ->allowedSorts(['id', 'name', 'city'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('suppliers.index', compact('suppliers'));
    }

    public function store(StoreSupplierRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['document_type'] = $data['document_type'] ?? 'nit';

        $supplier = Supplier::create($data);

        // Alta rápida desde la pantalla de compras (modal): devuelve el proveedor para seleccionarlo al instante.
        if ($request->expectsJson()) {
            return response()->json($supplier->only('id', 'name', 'document', 'document_type'), 201);
        }

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor registrado exitosamente.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['purchases' => fn ($q) => $q->latest('id')->limit(15)]);
        $supplier->loadCount('purchases');

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (empty($data['document_type'])) {
            unset($data['document_type']);
        }

        $supplier->update($data);

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(Supplier $supplier)
    {
        if ($supplier->purchases()->exists()) {
            return redirect()->route('suppliers.index')
                ->with('error', 'No se puede eliminar el proveedor: tiene compras asociadas. Puedes desactivarlo en su lugar.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', 'Proveedor eliminado exitosamente.');
    }
}
