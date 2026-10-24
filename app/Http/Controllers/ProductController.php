<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizer;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request)
    {
        $products = QueryBuilder::for(Product::class)
            ->with('category', 'warehouses', 'components')
            ->allowedFilters([
                AllowedFilter::scope('buscar', 'search'),

                AllowedFilter::exact('currentCategory', 'category_id'),
            ])
            ->allowedSorts(['id', 'name', 'sku'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        $categories = Category::orderBy('name')->get();
        $units = \App\Models\Unit::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('products.index', compact('products', 'categories', 'units'));
    }

    public function store(StoreProductRequest $request, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        // La foto se guarda con el ID del producto como nombre de archivo, así que se sube
        // después de crearlo (todavía no existe el ID al validar el formulario).
        $photo = $request->file('photo');
        unset($data['photo']);

        $withUnit = $request->boolean('create_unit');
        $unit = $withUnit ? [
            'unit_id' => $data['unit_id'],
            'factor' => 1,
            'price' => $data['unit_price'],
            'is_base' => true,
            'barcode' => $data['sku'], // un solo código: el SKU del producto sirve también de código de barras de su unidad base
        ] : null;

        unset($data['create_unit'], $data['unit_id'], $data['unit_price']);

        $productUnit = null;

        // El producto y su unidad base se crean juntos: o quedan los dos o ninguno.
        $product = DB::transaction(function () use ($data, $unit, $photo, $images, &$productUnit) {
            $product = Product::create($data);

            if ($photo) {
                $product->update(['photo' => $images->store($photo, 'products', 'photo', field: 'photo', name: (string) $product->id)]);
            }

            if ($unit) {
                $productUnit = $product->units()->create($unit);
            }

            return $product;
        });

        // Alta rápida desde otra pantalla (ej. el buscador de una compra): responde con el producto y su
        // unidad recién creados para que se puedan usar de inmediato, sin recargar la página.
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'product' => $product,
                'unit' => $productUnit ? [
                    'id' => $productUnit->id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'unit' => $productUnit->name,
                    'sku' => $product->sku,
                    'barcode' => $productUnit->barcode,
                    'factor' => (float) $productUnit->factor,
                    'cost' => 0.0,
                    'photo_url' => $product->photo_url,
                ] : null,
            ], 201);
        }

        return redirect()->route('products.index')
            ->with('success', $withUnit
                ? 'Producto registrado con su unidad base.'
                : 'Producto registrado exitosamente.');
    }

    public function show(Product $product)
    {
        $product->load('category', 'units', 'warehouses');

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();
        $units = \App\Models\Unit::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $product->load('units');

        return view('products.edit', compact('product', 'categories', 'units'));
    }

    public function update(UpdateProductRequest $request, Product $product, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('photo')) {
            if ($product->photo) {
                Storage::disk('uploads')->delete($product->photo);
            }

            $data['photo'] = $images->store($request->file('photo'), 'products', 'photo', field: 'photo', name: (string) $product->id);
        }

        // Unidad base opcional: solo se crea si el producto todavía no tiene una (el código de barras es el SKU)
        $unit = $request->boolean('create_unit') ? [
            'unit_id' => $data['unit_id'],
            'factor' => 1,
            'price' => $data['unit_price'],
            'is_base' => true,
            'barcode' => $data['sku'],
        ] : null;

        unset($data['create_unit'], $data['unit_id'], $data['unit_price']);

        DB::transaction(function () use ($product, $data, $unit) {
            $product->update($data);

            if ($unit) {
                $product->units()->create($unit);
            }
        });

        return redirect()->route('products.index')
            ->with('success', $unit ? 'Producto actualizado y su unidad de venta creada.' : 'Producto actualizado exitosamente.');
    }

    public function destroy(Product $product)
    {
        if ($product->photo) {
            Storage::disk('uploads')->delete($product->photo);
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }
}
