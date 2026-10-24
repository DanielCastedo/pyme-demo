<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreProductUnitRequest;
use App\Http\Requests\UpdateProductUnitRequest;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductUnitController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ProductUnit::class, 'product_unit');
    }

    public function index(Request $request)
    {
        $productUnits = QueryBuilder::for(ProductUnit::class)
            ->with('product')
            ->allowedFilters([
                AllowedFilter::callback('buscar', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        $q->where('name', 'like', "%{$value}%")
                            ->orWhere('barcode', 'like', "%{$value}%")
                            ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$value}%"));
                    });
                }),
                AllowedFilter::exact('currentProduct', 'product_id'),
            ])
            ->allowedSorts(['id', 'name', 'factor', 'price'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);
        $units = DB::table('units')->where('is_active', true)->orderBy('name')->pluck('name', 'id');

        return view('product-units.index', compact('productUnits', 'products', 'units'));
    }

    public function store(StoreProductUnitRequest $request)
    {
        $data = $request->validated();
        $data['is_base'] = $request->boolean('is_base');

        if ($data['is_base']) {
            ProductUnit::where('product_id', $data['product_id'])->update(['is_base' => false]);
        }

        ProductUnit::create($data);

        return redirect()->route('product-units.index')
            ->with('success', 'Unidad de producto registrada exitosamente.');
    }

    public function show(ProductUnit $productUnit)
    {
        $productUnit->load('product');

        return view('product-units.show', compact('productUnit'));
    }

    public function edit(ProductUnit $productUnit)
    {
        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);
        // Las activas y, si la presentación usa una inactiva, esa también para no perderla al editar
        $units = DB::table('units')->where('is_active', true)->orWhere('id', $productUnit->unit_id)->orderBy('name')->pluck('name', 'id');

        return view('product-units.edit', compact('productUnit', 'products', 'units'));
    }

    public function update(UpdateProductUnitRequest $request, ProductUnit $productUnit)
    {
        $data = $request->validated();
        $data['is_base'] = $request->boolean('is_base');

        if ($data['is_base']) {
            ProductUnit::where('product_id', $data['product_id'])
                ->where('id', '!=', $productUnit->id)
                ->update(['is_base' => false]);
        }

        $productUnit->update($data);

        return redirect()->route('product-units.index')
            ->with('success', 'Unidad de producto actualizada exitosamente.');
    }

    public function destroy(ProductUnit $productUnit)
    {
        if ($productUnit->is_base) {
            return redirect()->route('product-units.index')
                ->with('error', 'No se puede eliminar la unidad base del producto. Marca otra unidad como base primero.');
        }

        if ($productUnit->orderItems()->exists() || $productUnit->quotationItems()->exists() || $productUnit->stockMovementItems()->exists()) {
            return redirect()->route('product-units.index')
                ->with('error', 'No se puede eliminar: esta unidad tiene pedidos, cotizaciones o movimientos de stock asociados.');
        }

        $productUnit->delete();

        return redirect()->route('product-units.index')
            ->with('success', 'Unidad de producto eliminada exitosamente.');
    }
}
