<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    public function __invoke(Request $request, string $type, string $file): BinaryFileResponse
    {
        abort_unless(in_array($type, ['css', 'js'], true), 404);
        abort_unless($file === basename($file) && preg_match('/^[A-Za-z0-9._-]+$/', $file), 404);

        $path = module_path('StockAdjustmentNew', 'Resources/assets/' . $type . '/' . $file);
        abort_unless(is_file($path), 404);

        $contentType = $type === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return response()->file($path, [
            'Content-Type' => $contentType,
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
