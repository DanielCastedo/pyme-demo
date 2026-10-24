<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(Request $request)
    {
        $customers = QueryBuilder::for(Customer::class)
            ->withCount('quotations')
            ->allowedFilters([
                AllowedFilter::scope('buscar', 'search'),
                AllowedFilter::exact('currentStatus', 'is_active'),
            ])
            ->allowedSorts(['id', 'name', 'city'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('customers.index', compact('customers'));
    }

    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['document_type'] = $data['document_type'] ?? 'ci';

        $customer = Customer::create($data);

        // Alta rápida desde un modal (ventas, proforma): devuelve el cliente para seleccionarlo al instante.
        if ($request->expectsJson()) {
            return response()->json($customer->only('id', 'name', 'document', 'document_type', 'phone', 'email', 'address'), 201);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Cliente registrado exitosamente.');
    }

    public function show(Customer $customer)
    {
        $customer->loadCount('orders');
        $customer->load(['quotations' => fn ($q) => $q->latest()->limit(15)]);

        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if (empty($data['document_type'])) {
            unset($data['document_type']); // el formulario de proformas no lo envía: se conserva el actual
        }

        $customer->update($data);

        return redirect()->route('customers.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists() || $customer->quotations()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'No se puede eliminar el cliente: tiene ventas, pedidos o proformas asociadas. Puedes desactivarlo en su lugar.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }
}
