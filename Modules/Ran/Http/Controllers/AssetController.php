<?php

namespace Modules\Ran\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AssetController extends RanController
{
    public function show(string $type, string $file): Response
    {
        if (! in_array($type, ['css','js'], true) || ! preg_match('/^[a-zA-Z0-9._-]+$/', $file)) {
            throw new NotFoundHttpException();
        }
        $path = module_path('Ran', 'Resources/assets/'.$type.'/'.$file);
        if (! is_file($path)) {
            throw new NotFoundHttpException();
        }
        return response(file_get_contents($path), 200, [
            'Content-Type' => $type === 'css' ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
