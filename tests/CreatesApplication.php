<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use RuntimeException;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->guardAgainstRealDatabase($app);

        return $app;
    }

    /**
     * Las pruebas usan RefreshDatabase, que ejecuta migrate:fresh: contra una base real borraría TODOS los datos.
     * Este freno corta la ejecución antes de tocar nada si la conexión no es SQLite en memoria (ver phpunit.xml).
     */
    private function guardAgainstRealDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Pruebas bloqueadas: la conexión es \"{$connection}\" ({$database}). Deben correr sobre SQLite en memoria "
                .'para no borrar la base de datos real. Revisa DB_CONNECTION y DB_DATABASE en phpunit.xml.'
            );
        }
    }
}
