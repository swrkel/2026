<?php

namespace Modules\DigitalBusinessCards\Support;

/**
 * Thin adapter over whichever QR library the host application has available.
 *
 * This module deliberately does NOT vendor its own QR encoder — a hand-rolled
 * Reed-Solomon implementation is a large, hard-to-verify surface area. Install
 * one of the supported packages and this class picks it up automatically:
 *
 *   composer require endroid/qr-code       (recommended)
 *   composer require bacon/bacon-qr-code
 *
 * With neither installed, available() returns false and the UI falls back to a
 * copyable share link instead of rendering a broken image.
 */
class QrCode
{
    public function driver(): string
    {
        $configured = config('digital-business-cards.qr.driver', 'auto');

        if ($configured !== 'auto') {
            return $configured;
        }

        return match (true) {
            class_exists(\Endroid\QrCode\Builder\Builder::class)    => 'endroid',
            class_exists(\BaconQrCode\Writer::class)                => 'bacon',
            default                                                 => 'none',
        };
    }

    public function available(): bool
    {
        return $this->driver() !== 'none';
    }

    /**
     * @return array{body: string, mime: string}|null
     */
    public function png(string $text): ?array
    {
        $size   = (int) config('digital-business-cards.qr.size', 512);
        $margin = (int) config('digital-business-cards.qr.margin', 16);

        return match ($this->driver()) {
            'endroid' => $this->viaEndroid($text, $size, $margin),
            'bacon'   => $this->viaBacon($text, $size, $margin),
            default   => null,
        };
    }

    /**
     * endroid/qr-code has moved its error-correction API between major
     * versions, so the richer call is attempted first and quietly degrades to
     * the builder surface that has been stable across v4, v5 and v6.
     */
    protected function viaEndroid(string $text, int $size, int $margin): array
    {
        $class = \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelMedium::class;

        try {
            if (class_exists($class)) {
                $result = \Endroid\QrCode\Builder\Builder::create()
                    ->data($text)
                    ->size($size)
                    ->margin($margin)
                    ->errorCorrectionLevel(new $class())
                    ->build();

                return ['body' => $result->getString(), 'mime' => $result->getMimeType()];
            }
        } catch (\Throwable $e) {
            // Fall through to the minimal builder below.
        }

        $result = \Endroid\QrCode\Builder\Builder::create()
            ->data($text)
            ->size($size)
            ->margin($margin)
            ->build();

        return ['body' => $result->getString(), 'mime' => $result->getMimeType()];
    }

    protected function viaBacon(string $text, int $size, int $margin): array
    {
        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, (int) round($margin / 8)),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );

        $svg = (new \BaconQrCode\Writer($renderer))->writeString($text);

        return ['body' => $svg, 'mime' => 'image/svg+xml'];
    }
}
