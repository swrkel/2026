<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'leasing'], function () {
    Route::get('health', [\Modules\Leasing\Http\Controllers\RouteClosures\ApiRouteController::class, 'handle1']);
});
