<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Detector de consultas N+1: al desarrollar, cada relación que se carga por lazy-loading dentro de una lista
        // queda anotada en el log (storage/logs). Solo avisa: nunca rompe una página. En producción está apagado.
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::handleLazyLoadingViolationUsing(function ($model, string $relation) {
            logger()->warning('N+1: se cargó la relación "'.$relation.'" de '.get_class($model).' una por una; usa with() / load() en la consulta.');
        });
    }
}
