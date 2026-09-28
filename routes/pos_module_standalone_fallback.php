<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;

/*
|--------------------------------------------------------------------------
| POS Holds hard fallback
|--------------------------------------------------------------------------
| The POS module provider is not currently booting on every request. Do not
| depend on Modules\POS controller autoloading here. Register the page using
| a tenant-aware closure and the module view path directly.
*/

$posViewsPath = base_path('Modules/POS/Resources/views');
if (is_dir($posViewsPath)) {
    View::addNamespace('pos', $posViewsPath);
}

$posLangPath = base_path('Modules/POS/Resources/lang');
if (is_dir($posLangPath)) {
    app('translator')->addNamespace('pos', $posLangPath);
}

if (! Route::has('pos.holds.index')) {
    Route::middleware([
        'web',
        InitializeTenancyByDomain::class,
        PreventAccessFromCentralDomains::class,
        ScopeSessions::class,
        'auth',
    ])->get('/pos-module/holds', function () use ($posViewsPath) {
        abort_unless(is_file($posViewsPath . '/holds/index.blade.php'), 404);

        return view('pos::holds.index');
    })->name('pos.holds.index');
}
