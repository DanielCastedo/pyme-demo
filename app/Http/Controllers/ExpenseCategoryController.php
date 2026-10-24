<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Categorías para agrupar los gastos (Servicios, Alquiler, Sueldos...).
 */
class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ExpenseCategory::class, 'expense_category');
    }

    public function index(Request $request)
    {
        $categories = QueryBuilder::for(ExpenseCategory::class)
            ->withCount('expenses')
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
                AllowedFilter::exact('currentStatus', 'is_active'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('expense-categories.index', compact('categories'));
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $category = ExpenseCategory::create([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Alta rápida desde el formulario de gastos (modal).
        if ($request->expectsJson()) {
            return response()->json($category->only('id', 'name'), 201);
        }

        return redirect()->route('expense-categories.index')->with('success', 'Categoría registrada exitosamente.');
    }

    public function edit(ExpenseCategory $expense_category)
    {
        return view('expense-categories.edit', ['category' => $expense_category]);
    }

    public function update(StoreExpenseCategoryRequest $request, ExpenseCategory $expense_category)
    {
        $expense_category->update([
            'name' => $request->validated('name'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        if ($expense_category->expenses()->exists()) {
            return redirect()->route('expense-categories.index')
                ->with('error', 'No se puede eliminar la categoría: tiene gastos asociados. Puedes desactivarla en su lugar.');
        }

        $expense_category->delete();

        return redirect()->route('expense-categories.index')->with('success', 'Categoría eliminada exitosamente.');
    }
}
