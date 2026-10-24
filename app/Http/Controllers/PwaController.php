<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Datos de la app instalable (PWA). No dependen de la base de datos, así que siempre se puede instalar:
 *  - el nombre sale de APP_NAME (.env);
 *  - el ícono sale de public/images/logo-pymes.png (reemplazando ese archivo cambia el ícono de la app).
 */
class PwaController extends Controller
{
    /** Imagen del ícono de la app: config('app.logo'), controlable desde APP_LOGO en el .env. */
    private static function logo(): string
    {
        return (string) config('app.logo', 'images/logo-pymes.png');
    }

    /** Tamaños de ícono que se sirven (los que pide el manifiesto y el de iPhone). */
    private const SIZES = [180, 192, 512];

    /** Manifiesto de la app: nombre del .env e ícono de logo-pymes.png. */
    public function manifest()
    {
        $name = (string) config('app.name');

        return response()->json([
            'id' => '/',
            'name' => $name,
            'short_name' => Str::limit($name, 12, ''),
            'description' => 'Sistema de gestión de ventas, inventario, compras y finanzas.',
            'lang' => 'es',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => '#ffffff',
            'theme_color' => '#059669',
            'icons' => self::icons(),
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /** Ícono cuadrado en PNG, del tamaño pedido, generado a partir de logo-pymes.png. */
    public function icon(Request $request, int $size)
    {
        abort_unless(in_array($size, self::SIZES, true), 404);

        $path = self::logoPath();
        abort_if($path === null || ! function_exists('imagecreatefromstring'), 404);

        $source = @imagecreatefromstring((string) file_get_contents($path));
        abort_if($source === false, 404);

        // Ícono "maskable": el logo ocupa ~70% para que Android pueda recortarlo sin cortar el dibujo.
        $maskable = $request->query('maskable') === 'maskable';
        $inner = $maskable ? (int) round($size * 0.7) : (int) round($size * 0.9);
        $sw = imagesx($source);
        $sh = imagesy($source);
        $scale = min($inner / $sw, $inner / $sh);
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));

        $canvas = imagecreatetruecolor($size, $size);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, (int) (($size - $dw) / 2), (int) (($size - $dh) / 2), 0, 0, $dw, $dh, $sw, $sh);

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();

        return new Response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Ruta completa de logo-pymes.png, o null si el archivo no existe. */
    public static function logoPath(): ?string
    {
        $path = public_path(self::logo());

        return is_file($path) ? $path : null;
    }

    /**
     * Íconos del manifiesto. Con GD se generan a medida (192, 512 y una versión con margen para Android); si el
     * servidor no tiene GD se usa logo-pymes.png tal cual, para que la app se pueda instalar igual.
     *
     * @return array<int, array<string, string>>
     */
    private static function icons(): array
    {
        $path = self::logoPath();

        if ($path === null) {
            return [];
        }

        if (! function_exists('imagecreatefromstring')) {
            [$width, $height] = @getimagesize($path) ?: [512, 512];

            return [['src' => '/'.self::logo(), 'sizes' => "{$width}x{$height}", 'type' => 'image/png', 'purpose' => 'any']];
        }

        return [
            ['src' => self::iconUrl(192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => self::iconUrl(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => self::iconUrl(512, true), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ];
    }

    /**
     * URL (relativa) del ícono del tamaño pedido. Si el servidor no tiene GD devuelve logo-pymes.png tal cual.
     * El parámetro v cambia cuando se reemplaza el archivo, para que el navegador no use un ícono viejo.
     */
    public static function iconUrl(int $size, bool $maskable = false): ?string
    {
        $path = self::logoPath();

        if ($path === null) {
            return null;
        }

        if (! function_exists('imagecreatefromstring')) {
            return '/'.self::logo();
        }

        return route('pwa.icon', array_filter([
            'size' => $size,
            'maskable' => $maskable ? 'maskable' : null,
            'v' => filemtime($path),
        ]), false);
    }
}
