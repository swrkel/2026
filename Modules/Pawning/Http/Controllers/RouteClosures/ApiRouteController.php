<?php

namespace Modules\Pawning\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;

/**
 * MA-002 - route closures moved out of Modules/Pawning/Routes/api.php.
 *
 * WHY: Laravel cannot run `php artisan route:cache` while ANY route is defined
 * with a closure. This installation has 8,613 routes across 349 files, and
 * without the cache every one is parsed and compiled on EVERY request,
 * including the login page. That is the multi-second delay.
 *
 * Only 35 closures across 14 files were blocking it.
 *
 * The method bodies are BYTE-IDENTICAL to the closures they replace. Nothing
 * was rewritten - the code simply lives in a class so the route can be cached.
 */
class ApiRouteController
{
    public function handle1()
    {
        return ['module' => 'Pawning', 'status' => 'ok'];
    }
}
