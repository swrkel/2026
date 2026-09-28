<?php

namespace Modules\SimpleAudit\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    public function show($type, $file)
    {
        if (!in_array($type, ['css','js'], true) || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
            abort(404);
        }
        $path = __DIR__ . '/../../Resources/assets/' . $type . '/' . $file;
        if (!is_file($path)) {
            abort(404);
        }
        $mime = $type === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
