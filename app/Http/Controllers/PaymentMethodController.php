<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizer;
use App\Http\Requests\StorePaymentMethodRequest;
use App\Http\Requests\UpdatePaymentMethodRequest;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(PaymentMethod::class, 'paymentMethod');
    }

    public function index(Request $request)
    {
        $paymentMethods = QueryBuilder::for(PaymentMethod::class)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('payments.index', compact('paymentMethods'));
    }

    public function store(StorePaymentMethodRequest $request, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_cash'] = $request->boolean('is_cash');

        if ($request->hasFile('photo')) {
            $data['photo'] = $images->store($request->file('photo'), 'payment-methods', 'qr', field: 'photo');
        }

        PaymentMethod::create($data);

        return redirect()->route('payments.index')
            ->with('success', 'Método de pago registrado exitosamente.');
    }

    public function show(PaymentMethod $paymentMethod)
    {
        return view('payments.show', compact('paymentMethod'));
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return view('payments.edit', compact('paymentMethod'));
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_cash'] = $request->boolean('is_cash');

        if ($request->hasFile('photo')) {
            if ($paymentMethod->photo) {
                Storage::disk('uploads')->delete($paymentMethod->photo);
            }

            $data['photo'] = $images->store($request->file('photo'), 'payment-methods', 'qr', field: 'photo');
        }

        $paymentMethod->update($data);

        return redirect()->route('payments.index')
            ->with('success', 'Método de pago actualizado exitosamente.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        if ($paymentMethod->photo) {
            Storage::disk('uploads')->delete($paymentMethod->photo);
        }

        $paymentMethod->delete();

        return redirect()->route('payments.index')
            ->with('success', 'Método de pago eliminado exitosamente.');
    }
}
