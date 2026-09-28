<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pawning'], function () {
    Route::get('health', [\Modules\Pawning\Http\Controllers\RouteClosures\ApiRouteController::class, 'handle1']);
});
