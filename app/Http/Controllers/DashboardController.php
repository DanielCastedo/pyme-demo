<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index()
    {
        $company = Company::find(1);
        $plans = SubscriptionPlan::where('active', true)->orderBy('price')->get();

        return view('pages/dashboard/dashboard', compact('company', 'plans'));
    }

    /**
     * Displays the analytics screen
     *
     * @return Factory|View
     */
    public function analytics()
    {
        return view('pages/dashboard/analytics');
    }

    /**
     * Displays the fintech screen
     *
     * @return Factory|View
     */
    public function fintech()
    {
        return view('pages/dashboard/fintech');
    }
}
