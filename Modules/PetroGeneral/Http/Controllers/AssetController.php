<?php

namespace Modules\PetroGeneral\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    private const JAVASCRIPT_FILES = [
        'app.js',
        'payment.js',
        'po_payment.js',
    ];

    public function javascript(string $file): BinaryFileResponse
    {
        abort_unless(in_array($file, self::JAVASCRIPT_FILES, true), 404);

        $path = dirname(__DIR__, 2) . '/Resources/assets/js/' . $file;
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
