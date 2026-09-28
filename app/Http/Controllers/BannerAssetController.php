<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BannerAssetController extends Controller
{
    /**
     * Serve a master banner from the persistent banner store.
     *
     * The store lives outside the Laravel deployment tree, so normal code
     * replacement cannot remove uploaded master banners.
     */
    public function show(string $path): BinaryFileResponse
    {
        $path = ltrim(str_replace('\\', '/', rawurldecode($path)), '/');

        if ($path === '' || Str::contains($path, ['..', "\0"])) {
            abort(404);
        }

        $disk = Storage::disk('banner_uploads');

        if (! $disk->exists($path)) {
            abort(404);
        }

        $absolutePath = $disk->path($path);
        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
