<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Storage;

/**
 * Logo del encabezado de los recibos (PDF): el logo de la sucursal si lo tiene cargado y, si no, public/images/logo-pymes.png.
 * Se convierte a un PNG chico en base64: DomPDF no lee WebP (el formato en que se guardan los logos) y así el PDF
 * no carga con una imagen de un megabyte.
 */
final class ReceiptLogo
{
    /** Logo por defecto: config('app.logo'), controlable desde APP_LOGO en el .env. */
    private static function defaultLogo(): string
    {
        return (string) config('app.logo', 'images/logo-pymes.png');
    }

    /** Alto máximo del logo en píxeles (se muestra a ~48 pt de alto). */
    private const MAX_HEIGHT = 160;

    /** Imagen lista para un <img src="...">, o null si no hay logo o el servidor no tiene GD. */
    public static function dataUri(?Branch $branch = null): ?string
    {
        $path = self::path($branch);

        if ($path === null || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));

        if ($source === false) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, self::MAX_HEIGHT / max(1, $height), self::MAX_HEIGHT * 2 / max(1, $width));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 255, 255, 255, 127));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagepng($canvas);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    /** Archivo del logo: el de la sucursal si existe; si no, el logo por defecto del proyecto. */
    private static function path(?Branch $branch): ?string
    {
        if ($branch?->logo) {
            $disk = Storage::disk('uploads');

            if ($disk->exists($branch->logo)) {
                return $disk->path($branch->logo);
            }
        }

        $default = public_path(self::defaultLogo());

        return is_file($default) ? $default : null;
    }
}
