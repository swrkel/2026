<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Customers\Entities\CustomerReference;

/**
 * Task 8046 - builds and renders the QR code for a customer reference.
 *
 * WHAT GOES IN THE QR
 *   Customer Name, the reference, and - for vehicles only - the fuel type.
 *   The reference is labelled "Vehicle No" when the reference is a vehicle.
 *   Human-readable plain text, so a phone's built-in camera shows something
 *   useful without a companion app installed.
 *
 * WHY THE PAYLOAD IS STORED BUT THE IMAGE IS NOT
 *   A QR code gets printed and stuck to a vehicle. Once printed, what it says
 *   is fixed. Storing the payload text at creation time means the printed
 *   sticker and the database always agree, even if the customer is later
 *   renamed. Storing a rendered PNG instead would bloat the row and pin us to
 *   one size and format; re-deriving the payload on every render would let a
 *   rename silently invalidate every sticker already in the field.
 *
 * RENDERING - AND WHY THERE IS A FALLBACK
 *   The Customers module does not ship a QR library and cannot add one to the
 *   host application's composer.json. So this service probes for whichever
 *   library the host already has, in order of preference, and reports what it
 *   found through capabilities().
 *
 *   If none is present, serverSideAvailable() returns false and the views fall
 *   back to rendering the QR in the browser from qr_payload. Print and PDF
 *   still work in that mode because both are driven from an open page. Only a
 *   future unattended/queued send would need a server-side library, which is
 *   why this is reported rather than hidden.
 */
class CustomerReferenceQrService
{
    /**
     * Default rendered size in pixels. Large enough to stay scannable after
     * being printed small and stuck on a windscreen.
     */
    public const DEFAULT_SIZE = 320;

    protected ?string $driver = null;

    protected bool $driverResolved = false;

    /**
     * Build the QR payload text for a reference.
     *
     * Kept as a pure string builder taking an explicit customer name so it can
     * be called before the row exists (on create) and re-called on edit.
     */
    public function buildPayload(string $customerName, bool $isVehicle, string $referenceNo, ?string $fuelTypeName = null): string
    {
        $lines = [];

        $lines[] = 'Customer Name: ' . trim($customerName);
        $lines[] = ($isVehicle ? 'Vehicle No: ' : 'Reference: ') . trim($referenceNo);

        // Fuel type is only part of the payload for vehicles, per the spec.
        if ($isVehicle) {
            $lines[] = 'Fuel Type: ' . trim((string) ($fuelTypeName ?: \Modules\Customers\Entities\CustomerReferenceFuelType::NOT_KNOWN_LABEL));
        }

        return implode("\n", $lines);
    }

    /**
     * Rebuild the payload from a saved reference.
     */
    public function buildPayloadForReference(CustomerReference $reference, string $customerName): string
    {
        return $this->buildPayload(
            $customerName,
            (bool) $reference->is_vehicle,
            (string) $reference->reference_no,
            $reference->fuel_type_name
        );
    }

    /**
     * A collision-resistant handle for the reference's QR.
     *
     * Used in URLs so a scan or a shared link never exposes a sequential row
     * id, which would otherwise let anyone enumerate other tenants' references.
     */
    public function generateToken(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Can the QR be rendered on the server?
     */
    public function serverSideAvailable(): bool
    {
        return $this->driver() !== null;
    }

    /**
     * Which library is being used, for the diagnostics line in the UI.
     */
    public function capabilities(): array
    {
        return [
            'driver' => $this->driver(),
            'server_side' => $this->serverSideAvailable(),
        ];
    }

    /**
     * Render the QR as inline SVG markup, or null when no library is present.
     *
     * SVG rather than PNG: it scales to any print size without going fuzzy,
     * needs no GD/Imagick extension, and embeds directly in both the print
     * view and the PDF view.
     */
    public function renderSvg(string $payload, int $size = self::DEFAULT_SIZE): ?string
    {
        $driver = $this->driver();
        if ($driver === null) {
            return null;
        }

        try {
            switch ($driver) {
                case 'simple-qrcode':
                    return (string) \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                        ->size($size)
                        ->margin(1)
                        ->errorCorrection('M')
                        ->generate($payload);

                case 'endroid':
                    return $this->renderWithEndroid($payload, $size);

                case 'milon-barcode':
                    /*
                     * PNG rather than SVG, deliberately.
                     *
                     * The legacy Vehicle No page in this application already
                     * generates its codes with DNS2D::getBarcodePNG(...,
                     * 'QRCODE'), so that exact call is known to work on this
                     * install. getBarcodeSVG exists too, but is not exercised
                     * anywhere here - and an untested code path is a poor
                     * choice for the one thing that has to render every time.
                     *
                     * The return value is an <img> tag rather than raw markup
                     * so callers can echo it uniformly regardless of driver.
                     */
                    $generator = new \Milon\Barcode\DNS2D();
                    $png = $generator->getBarcodePNG($payload, 'QRCODE');

                    if (empty($png)) {
                        return null;
                    }

                    return '<img src="data:image/png;base64,' . $png . '" alt="QR code"'
                        . ' style="width:' . (int) $size . 'px;height:' . (int) $size . 'px;">';

                case 'bacon':
                    return $this->renderWithBacon($payload, $size);
            }
        } catch (\Throwable $e) {
            // A QR failure must never take down the list page. Log it and let
            // the caller fall back to browser-side rendering.
            Log::error('Customer Reference QR rendering failed', [
                'driver' => $driver,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Render as a base64 data URI, for embedding in HTML/PDF/email bodies.
     */
    public function renderSvgDataUri(string $payload, int $size = self::DEFAULT_SIZE): ?string
    {
        $svg = $this->renderSvg($payload, $size);
        if ($svg === null) {
            return null;
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Detect the available QR library once per request.
     *
     * Ordered by how well each one suits this use case, not alphabetically:
     * simple-qrcode and endroid are purpose-built QR libraries with good SVG
     * output; milon/barcode is a general barcode library that happens to do
     * QRCODE and is common in this ERP family; BaconQrCode is the low-level
     * library the first two are usually built on, so it is the last resort.
     */
    protected function driver(): ?string
    {
        if ($this->driverResolved) {
            return $this->driver;
        }

        $this->driverResolved = true;

        $configured = config('customers.qr_driver');
        if (! empty($configured)) {
            // An explicit driver is honoured even if detection would have
            // picked another, so a tenant can pin the one it has tested.
            $this->driver = (string) $configured;

            return $this->driver;
        }

        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            return $this->driver = 'simple-qrcode';
        }

        if (class_exists(\Endroid\QrCode\Builder\Builder::class) || class_exists(\Endroid\QrCode\QrCode::class)) {
            return $this->driver = 'endroid';
        }

        if (class_exists(\Milon\Barcode\DNS2D::class)) {
            return $this->driver = 'milon-barcode';
        }

        if (class_exists(\BaconQrCode\Writer::class)) {
            return $this->driver = 'bacon';
        }

        return $this->driver = null;
    }

    /**
     * Endroid has two incompatible generations of API in the wild (v3/v4 use a
     * constructor, v4.4+/v5 use a Builder). Both are handled so this does not
     * depend on which one the host pinned.
     */
    protected function renderWithEndroid(string $payload, int $size): ?string
    {
        if (class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            $result = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\SvgWriter())
                ->data($payload)
                ->size($size)
                ->margin(4)
                ->build();

            return $result->getString();
        }

        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            $qr = new \Endroid\QrCode\QrCode($payload);
            $qr->setSize($size);

            if (method_exists($qr, 'setWriter') && class_exists(\Endroid\QrCode\Writer\SvgWriter::class)) {
                $qr->setWriter(new \Endroid\QrCode\Writer\SvgWriter());
            }

            return $qr->writeString();
        }

        return null;
    }

    protected function renderWithBacon(string $payload, int $size): ?string
    {
        if (! class_exists(\BaconQrCode\Renderer\ImageRenderer::class)) {
            return null;
        }

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle($size, 1),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );

        $writer = new \BaconQrCode\Writer($renderer);

        return $writer->writeString($payload);
    }
}
