<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $company = Company::latest()->first();
            $view->with('companyName', $company?->name ?? config('app.name'));

            $manifestPath = public_path('build/manifest.json');
            $appVersion = file_exists($manifestPath)
                ? md5_file($manifestPath)
                : (string) filemtime(base_path('composer.lock'));
            $view->with('appVersion', $appVersion);
        });
    }
}
