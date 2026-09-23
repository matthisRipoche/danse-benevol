<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeSvg
{
    /**
     * Render the given content as an inline SVG QR code (without the XML declaration).
     */
    public function render(string $content, int $size = 160): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $writer->writeString($content)));
    }
}
