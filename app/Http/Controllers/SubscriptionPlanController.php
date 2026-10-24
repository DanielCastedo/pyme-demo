<?php

namespace App\Http\Controllers;

use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreSubscriptionPlanRequest;
use App\Http\Requests\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request)
    {
        $plans = QueryBuilder::for(SubscriptionPlan::class)
            ->allowedFilters([AllowedFilter::partial('buscar', 'name')])
            ->allowedSorts(['id', 'name', 'price'])
            ->defaultSort('id')
            ->paginate(10)
            ->appends($request->query());

        return view('subscription-plans.index', compact('plans'));
    }

    public function store(StoreSubscriptionPlanRequest $request, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['features'] = $this->parseFeatures($request->features);
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('qr_image')) {
            $data['qr_image'] = $images->store($request->file('qr_image'), 'plan-qr', 'qr', field: 'qr_image');
        }

        SubscriptionPlan::create($data);

        return redirect()->route('subscription-plans.index')
            ->with('success', 'Plan creado exitosamente.');
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        return view('subscription-plans.show', ['plan' => $subscriptionPlan]);
    }

    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('subscription-plans.edit', ['plan' => $subscriptionPlan]);
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan, ImageOptimizer $images)
    {
        $data = $request->validated();
        $data['features'] = $this->parseFeatures($request->features);
        $data['active'] = $request->boolean('active', true);

        if ($request->hasFile('qr_image')) {
            if ($subscriptionPlan->qr_image) {
                Storage::disk('uploads')->delete($subscriptionPlan->qr_image);
            }

            $data['qr_image'] = $images->store($request->file('qr_image'), 'plan-qr', 'qr', field: 'qr_image');
        }

        $subscriptionPlan->update($data);

        return redirect()->route('subscription-plans.index')
            ->with('success', 'Plan actualizado exitosamente.');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return redirect()->route('subscription-plans.index')
            ->with('success', 'Plan eliminado exitosamente.');
    }

    private function parseFeatures(?string $features): array
    {
        if (! $features) {
            return [];
        }

        return collect(explode("\n", $features))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}
