<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Optimiza imágenes subidas antes de guardarlas: corrige la orientación EXIF, reduce
 * las dimensiones excesivas (nunca agranda) y recomprime a WebP. Usa GD, sin dependencias
 * extra. Si el resultado no es más liviano que el original, se conserva el original.
 */
class ImageOptimizer
{
    /**
     * Perfiles por tipo de imagen: dimensión máxima (px) y calidad WebP (0-100).
     * Los QR y logos llevan más calidad para no perder nitidez.
     */
    private const PRESETS = [
        'photo' => ['max' => 1600, 'quality' => 80],
        'logo' => ['max' => 800, 'quality' => 90],
        'qr' => ['max' => 1200, 'quality' => 92],
    ];

    /**
     * Tope de píxeles a decodificar: GD necesita ~4-5 bytes por píxel en memoria, y una
     * foto de 100 MP podría agotarla aunque el archivo pese poco.
     */
    private const MAX_PIXELS = 40_000_000;

    /**
     * Optimiza y guarda en el disco; devuelve la ruta relativa para guardar en la BD.
     * Si se indica $name, el archivo se guarda con ese nombre (por ejemplo, el ID del
     * registro dueño de la imagen) en vez de un nombre aleatorio.
     */
    public function store(UploadedFile $file, string $directory, string $preset = 'photo', string $disk = 'uploads', string $field = 'image', ?string $name = null): string
    {
        [$bytes, $extension] = $this->optimizeFile($file->getRealPath(), $preset, $field);

        $path = trim($directory, '/').'/'.($name !== null ? $name : Str::random(40)).'.'.$extension;

        Storage::disk($disk)->put($path, $bytes);

        return $path;
    }

    /**
     * Devuelve un UploadedFile ya optimizado (para flujos que guardan por su cuenta, como la
     * foto de perfil de Jetstream).
     */
    public function optimizeUpload(UploadedFile $file, string $preset = 'photo', string $field = 'photo'): UploadedFile
    {
        [$bytes, $extension] = $this->optimizeFile($file->getRealPath(), $preset, $field);

        $tmp = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($tmp, $bytes);
        register_shutdown_function(static fn () => @unlink($tmp));

        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.'.$extension;

        return new UploadedFile($tmp, $name, null, null, true);
    }

    /**
     * Extensión según el contenido real del archivo (no según el nombre que mandó el cliente), o null si
     * no es una imagen admitida. Las imágenes se sirven desde public/uploads: con la extensión del cliente,
     * un archivo que pasa la validación pero se llama .html, .svg o .php quedaría publicado tal cual.
     */
    public function detectExtension(string $path): ?string
    {
        $info = @getimagesize($path);

        return match ($info[2] ?? null) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
            default => null,
        };
    }

    /**
     * Núcleo: lee el archivo y devuelve [bytes, extensión]. Si no se puede recomprimir, devuelve el
     * original sin tocar; si ni siquiera es una imagen admitida, lo rechaza.
     *
     * @return array{0: string, 1: string}
     */
    public function optimizeFile(string $path, string $preset = 'photo', string $field = 'image'): array
    {
        $originalExtension = $this->detectExtension($path);

        if ($originalExtension === null) {
            throw ValidationException::withMessages([
                $field => 'El archivo no es una imagen válida (JPG, PNG o WEBP).',
            ]);
        }

        $original = file_get_contents($path);
        $settings = self::PRESETS[$preset] ?? self::PRESETS['photo'];

        $info = @getimagesize($path);

        if (! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return [$original, $originalExtension];
        }

        [$width, $height] = $info;

        $maxPixels = $this->maxPixelsForAvailableMemory();

        if ($width * $height > $maxPixels) {
            throw ValidationException::withMessages([
                $field => 'La imagen tiene demasiados píxeles ('.round($width * $height / 1_000_000).' MP). Redúcela antes de subirla (máximo '.floor($maxPixels / 1_000_000).' MP).',
            ]);
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
        };

        if (! $image) {
            return [$original, $originalExtension];
        }

        $changed = false;

        if ($info[2] === IMAGETYPE_JPEG) {
            [$image, $rotated] = $this->applyExifOrientation($image, $path);
            $changed = $rotated;
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest > $settings['max']) {
            $scale = $settings['max'] / $longest;
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);

            if ($resized) {
                imagedestroy($image);
                $image = $resized;
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $changed = true;
            }
        }

        ob_start();
        imagewebp($image, null, $settings['quality']);
        $webp = ob_get_clean();
        imagedestroy($image);

        // Si recomprimir no ayudó (imagen ya chica/optimizada) se deja el original tal cual.
        if (! $changed && strlen($webp) >= strlen($original)) {
            return [$original, $originalExtension];
        }

        return [$webp, 'webp'];
    }

    /**
     * GD ocupa ~5 bytes por píxel entre la imagen decodificada y su copia redimensionada. Se limita
     * a MAX_PIXELS y también a lo que quepa en el memory_limit del servidor (con margen), para
     * rechazar con un mensaje claro en vez de morir con un error fatal de memoria.
     */
    private function maxPixelsForAvailableMemory(): int
    {
        $raw = trim((string) ini_get('memory_limit'));
        $limit = (int) $raw;

        if ($limit <= 0) {
            return self::MAX_PIXELS; // -1 = sin límite
        }

        $limit *= match (strtolower(substr($raw, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        $available = max(0, $limit - memory_get_usage(true));

        return (int) min(self::MAX_PIXELS, $available * 0.6 / 5);
    }

    /**
     * Al recomprimir se pierden los metadatos EXIF, así que la rotación de las fotos de celular
     * hay que aplicarla a los píxeles; si no, las fotos verticales quedarían acostadas.
     *
     * @return array{0: \GdImage, 1: bool}
     */
    private function applyExifOrientation(\GdImage $image, string $path): array
    {
        if (! function_exists('exif_read_data')) {
            return [$image, false];
        }

        $exif = @exif_read_data($path);
        $angle = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return [$image, false];
        }

        $rotated = imagerotate($image, $angle, 0);

        if (! $rotated) {
            return [$image, false];
        }

        imagedestroy($image);

        return [$rotated, true];
    }
}
