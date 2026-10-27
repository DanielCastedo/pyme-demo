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
     * Este freno corta la ejecución antes de tocar nada si la conexión no es la SQLite de pruebas (ver phpunit.xml).
     */
    private function guardAgainstRealDatabase(Application $app): void
    {
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get("database.connections.{$connection}.database");
        $testingFile = $app->databasePath('testing.sqlite');

        // phpunit.xml la da relativa al proyecto (database/testing.sqlite): se pasa a ruta absoluta.
        if ($connection === 'sqlite' && $database === 'database/testing.sqlite') {
            $database = $testingFile;
            $app['config']->set("database.connections.{$connection}.database", $database);
        }

        if ($connection !== 'sqlite' || ! in_array($database, [':memory:', $testingFile], true)) {
            throw new RuntimeException(
                "Pruebas bloqueadas: la conexión es \"{$connection}\" ({$database}). Deben correr sobre SQLite "
                .'(database/testing.sqlite) para no borrar la base de datos real. Revisa DB_CONNECTION y DB_DATABASE en phpunit.xml.'
            );
        }

        // Base en archivo: RefreshDatabase la migra UNA vez por corrida y cada prueba va en una transacción
        // que se deshace al final (en memoria tendría que migrar las 47 migraciones antes de cada prueba).
        if ($database === $testingFile && ! file_exists($testingFile)) {
            touch($testingFile);
        }
    }
}
