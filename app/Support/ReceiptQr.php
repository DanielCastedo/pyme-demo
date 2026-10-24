<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Código QR de los recibos (PDF). Se genera como SVG: no necesita GD ni Imagick y DomPDF lo dibuja nítido.
 */
final class ReceiptQr
{
    /** Imagen lista para un <img src="..."> con el QR del texto indicado. */
    public static function dataUri(string $text, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 0), new SvgImageBackEnd()));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($text));
    }
}
