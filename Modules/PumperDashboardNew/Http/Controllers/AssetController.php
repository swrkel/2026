<?php

namespace Modules\PumperDashboardNew\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    public function show(string $type, string $file): BinaryFileResponse|Response
    {
        abort_unless(in_array($type, ['css', 'js', 'images'], true), 404);
        abort_if(str_contains($file, '..') || str_contains($file, '/') || str_contains($file, '\\'), 404);
        $path = dirname(__DIR__, 2) . '/Resources/assets/' . $type . '/' . $file;
        abort_unless(is_file($path), 404);
        $mime = match ($type) {
            'css' => 'text/css; charset=UTF-8',
            'js' => 'application/javascript; charset=UTF-8',
            default => mime_content_type($path) ?: 'application/octet-stream',
        };
        return response()->file($path, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=86400']);
    }
}
