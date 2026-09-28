<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function show(string $type, string $file)
    {
        $type = strtolower($type);
        $file = basename($file);

        if (!in_array($type, ['css', 'js'], true) || !preg_match('/^[A-Za-z0-9_\-.]+$/', $file)) {
            abort(404);
        }

        $path = realpath(__DIR__ . '/../../Resources/' . $type . '/' . $file);
        $base = realpath(__DIR__ . '/../../Resources/' . $type);

        if (!$path || !$base || !Str::startsWith($path, $base) || !is_file($path)) {
            abort(404);
        }

        $contentType = $type === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return response(file_get_contents($path), 200)
            ->header('Content-Type', $contentType)
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
