<?php

namespace App\Console\Commands;

use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OptimizeUploadedImages extends Command
{
    protected $signature = 'images:optimize {--dry-run : Solo muestra cuánto se ahorraría, sin modificar nada}';

    protected $description = 'Recomprime las imágenes que ya están subidas (productos, logos, QR, etc.) y actualiza su ruta en la base de datos.';

    /**
     * tabla => [columna => [carpeta, perfil]]
     */
    private const TARGETS = [
        'products' => ['photo' => ['products', 'photo']],
        'payments' => ['photo' => ['payment-methods', 'qr']],
        'branches' => [
            'logo' => ['logos', 'logo'],
            'payment_qr_image' => ['branch-qr', 'qr'],
        ],
        'subscription_plans' => ['qr_image' => ['plan-qr', 'qr']],
    ];

    public function handle(ImageOptimizer $optimizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $disk = Storage::disk('uploads');
        $before = 0;
        $after = 0;
        $count = 0;

        if (! $dryRun) {
            $this->cleanupOrphans($disk);
        }

        foreach (self::TARGETS as $table => $columns) {
            foreach ($columns as $column => [$directory, $preset]) {
                $rows = DB::table($table)->whereNotNull($column)->where($column, '!=', '')->get(['id', $column]);

                foreach ($rows as $row) {
                    $path = $row->{$column};

                    if (! $disk->exists($path)) {
                        $this->warn("[{$table}.{$column} #{$row->id}] archivo no encontrado: {$path}");

                        continue;
                    }

                    $absolute = $disk->path($path);
                    $originalSize = filesize($absolute);

                    try {
                        [$bytes, $extension] = $optimizer->optimizeFile($absolute, $preset, $column, pathinfo($path, PATHINFO_EXTENSION));
                    } catch (ValidationException $e) {
                        $this->warn("[{$table}.{$column} #{$row->id}] omitida: ".collect($e->errors())->flatten()->first());

                        continue;
                    }

                    // Solo se reemplaza si realmente pesa menos.
                    if (strlen($bytes) >= $originalSize) {
                        continue;
                    }

                    $this->line(sprintf('[%s.%s #%d] %s KB -> %s KB', $table, $column, $row->id, round($originalSize / 1024), round(strlen($bytes) / 1024)));

                    if ($dryRun) {
                        continue;
                    }

                    // El archivo nuevo se escribe con nombre al azar y recién se actualiza la BD si eso
                    // funciona: así, si algo se corta a mitad de camino (una excepción, el comando
                    // interrumpido), la fila sigue apuntando al archivo viejo -- que todavía existe -- en
                    // vez de quedar apuntando a nada. El archivo viejo se borra al final, una vez que la
                    // BD ya quedó apuntando al nuevo.
                    $newPath = $directory.'/'.Str::random(40).'.'.$extension;

                    try {
                        $disk->put($newPath, $bytes);
                        DB::table($table)->where('id', $row->id)->update([$column => $newPath]);
                    } catch (\Throwable $e) {
                        $disk->delete($newPath); // no llegó a quedar referenciado: no sirve dejarlo
                        $this->warn("[{$table}.{$column} #{$row->id}] no se pudo guardar, se descarta: ".$e->getMessage());

                        continue;
                    }

                    $disk->delete($path);

                    $before += $originalSize;
                    $after += strlen($bytes);
                    $count++;
                }
            }
        }

        $this->info(sprintf(
            '%s%d imágenes optimizadas: %s KB -> %s KB (ahorro %s KB).',
            $dryRun ? '[simulación] ' : '',
            $count,
            round($before / 1024),
            round($after / 1024),
            round(($before - $after) / 1024)
        ));

        return self::SUCCESS;
    }

    /**
     * Borra archivos de las carpetas de TARGETS que ningún registro referencia. Deja la casa en orden
     * antes de optimizar, incluso si algo interrumpió una corrida anterior justo entre guardar el
     * archivo nuevo y borrar el viejo (un Ctrl+C no pasa por el catch de handle()).
     */
    private function cleanupOrphans(\Illuminate\Contracts\Filesystem\Filesystem $disk): void
    {
        // Un mismo directorio (p. ej. "products") solo lo llena una tabla.columna: se junta qué
        // archivo referencia cada una antes de mirar qué sobra en el disco.
        $referencedByDirectory = [];

        foreach (self::TARGETS as $table => $columns) {
            foreach ($columns as $column => [$directory, $preset]) {
                foreach (DB::table($table)->whereNotNull($column)->pluck($column) as $path) {
                    $referencedByDirectory[$directory][basename($path)] = true;
                }
            }
        }

        foreach (self::TARGETS as $columns) {
            foreach ($columns as [$directory, $preset]) {
                foreach ($disk->files($directory) as $file) {
                    if (empty($referencedByDirectory[$directory][basename($file)])) {
                        $disk->delete($file);
                        $this->warn("[{$directory}] huérfano borrado (de una corrida anterior interrumpida): {$file}");
                    }
                }
            }
        }
    }
}
