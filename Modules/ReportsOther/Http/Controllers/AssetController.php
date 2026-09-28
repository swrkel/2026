<?php

namespace Modules\ReportsOther\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AssetController extends Controller
{
    public function __invoke(Request $request, string $type, string $file): Response|BinaryFileResponse
    {
        $allowed = [
            'css' => ['reports-other.css' => 'text/css; charset=UTF-8'],
            'js' => ['reports-other.js' => 'application/javascript; charset=UTF-8'],
        ];

        abort_unless(isset($allowed[$type][$file]), 404);
        $path = __DIR__.'/../../Resources/assets/'.$type.'/'.$file;
        abort_unless(is_file($path), 404);

        // REO-PERF-002: assets are versioned in the layout (?v=...), so they can
        // be cached aggressively instead of re-running Laravel for every tab/page.
        $etag = '"'.sha1_file($path).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304)->withHeaders([
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return response()->file($path, [
            'Content-Type' => $allowed[$type][$file],
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }
}
