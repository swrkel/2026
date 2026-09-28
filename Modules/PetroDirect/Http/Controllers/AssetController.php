<?php

namespace Modules\PetroDirect\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    private const JAVASCRIPT_FILES = [
        'app.js',
        'direct-settlement-meter-sale.js',
        'payment.js',
        'payment_tabs.js',
        'petro_payment.js',
        'po_payment.js',
    ];

    private const DOWNLOAD_FILES = [
        'import_fuel_tanks.xls',
        'import_pumps.xls',
    ];

    public function javascript(string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, self::JAVASCRIPT_FILES, true), 404);

        return response()->file(
            $this->assetPath('js', $file),
            [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Cache-Control' => 'private, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    public function download(string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, self::DOWNLOAD_FILES, true), 404);

        return response()->download(
            $this->assetPath('files', $file),
            $file,
            ['Content-Type' => 'application/vnd.ms-excel']
        );
    }

    private function assetPath(string $directory, string $file): string
    {
        $path = dirname(__DIR__, 2) . '/Resources/assets/' . $directory . '/' . $file;

        abort_unless(is_file($path), 404);

        return $path;
    }
}
