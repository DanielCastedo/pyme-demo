<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BranchController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Branch::class, 'branch');
    }

    public function index(Request $request)
    {
        $branches = QueryBuilder::for(Branch::class)
            ->allowedFilters([
                AllowedFilter::partial('buscar', 'name'),
            ])
            ->allowedSorts(['id', 'name'])
            ->defaultSort('name')
            ->paginate(10)
            ->appends($request->query());

        return view('branches.index', compact('branches'));
    }

    public function store(StoreBranchRequest $request, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $data['logo'] = $images->store($request->file('logo'), 'logos', 'logo', field: 'logo');
        }

        if ($request->hasFile('payment_qr_image')) {
            $data['payment_qr_image'] = $images->store($request->file('payment_qr_image'), 'branch-qr', 'qr', field: 'payment_qr_image');
        }

        Branch::create($data);

        return redirect()->route('branches.index')
            ->with('success', 'Sucursal registrada exitosamente.');
    }

    public function show(Branch $branch)
    {
        $branch->loadCount('users', 'cashboxes', 'warehouses');

        return view('branches.show', compact('branch'));
    }

    public function edit(Branch $branch)
    {
        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            if ($branch->logo) {
                Storage::disk('uploads')->delete($branch->logo);
            }

            $data['logo'] = $images->store($request->file('logo'), 'logos', 'logo', field: 'logo');
        }

        if ($request->hasFile('payment_qr_image')) {
            if ($branch->payment_qr_image) {
                Storage::disk('uploads')->delete($branch->payment_qr_image);
            }

            $data['payment_qr_image'] = $images->store($request->file('payment_qr_image'), 'branch-qr', 'qr', field: 'payment_qr_image');
        }

        $branch->update($data);

        return redirect()->route('branches.index')
            ->with('success', 'Sucursal actualizada exitosamente.');
    }

    public function destroy(Branch $branch)
    {
        if ($branch->users()->exists() || $branch->cashboxes()->exists() || $branch->warehouses()->exists() || $branch->transactions()->exists()) {
            return redirect()->route('branches.index')
                ->with('error', 'No se puede eliminar la sucursal: tiene usuarios, cajas, almacenes o transacciones asociadas.');
        }

        if ($branch->logo) {
            Storage::disk('uploads')->delete($branch->logo);
        }

        if ($branch->payment_qr_image) {
            Storage::disk('uploads')->delete($branch->payment_qr_image);
        }

        $branch->delete();

        return redirect()->route('branches.index')
            ->with('success', 'Sucursal eliminada exitosamente.');
    }
}
