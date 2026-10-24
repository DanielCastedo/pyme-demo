<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $companies = QueryBuilder::for(Company::class)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('id')
            ->paginate(10)
            ->appends($request->query());

        return view('companies.index', compact('companies'));
    }

    public function store(StoreCompanyRequest $request)
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Solo el superadmin puede registrar nuevas empresas.');

        Company::create($request->validated());

        return redirect()->route('companies.index')
            ->with('success', 'Empresa registrada exitosamente.');
    }

    public function show(Company $company)
    {
        return view('companies.show', compact('company'));
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return redirect()->route('companies.index')
            ->with('success', 'Empresa actualizada exitosamente.');
    }

    public function destroy(Company $company)
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403, 'Solo el superadmin puede eliminar una empresa.');

        $company->delete();

        return redirect()->route('companies.index')
            ->with('success', 'Empresa eliminada exitosamente.');
    }
}
