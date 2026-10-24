<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Requests\StorePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Promotion::class, 'promotion');
    }

    public function index(Request $request)
    {
        $promotions = QueryBuilder::for(Promotion::class)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name', 'start_date', 'end_date'])
            ->defaultSort('-start_date')
            ->paginate(10)
            ->appends($request->query());

        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);

        return view('promotions.index', compact('promotions', 'products'));
    }

    public function store(StorePromotionRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        $promotion = Promotion::create($data);
        $promotion->products()->sync($productIds);

        return redirect()->route('promotions.index')
            ->with('success', 'Promoción registrada exitosamente.');
    }

    public function show(Promotion $promotion)
    {
        $promotion->load('products');

        return view('promotions.show', compact('promotion'));
    }

    public function edit(Promotion $promotion)
    {
        $products = DB::table('products')->orderBy('name')->get(['id', 'name']);
        $selectedProductIds = $promotion->products()->pluck('products.id')->toArray();

        return view('promotions.edit', compact('promotion', 'products', 'selectedProductIds'));
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        $promotion->update($data);
        $promotion->products()->sync($productIds);

        return redirect()->route('promotions.index')
            ->with('success', 'Promoción actualizada exitosamente.');
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return redirect()->route('promotions.index')
            ->with('success', 'Promoción eliminada exitosamente.');
    }
}
