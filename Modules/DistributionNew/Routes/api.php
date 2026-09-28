<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Api\DisnewMobileApiController;

Route::post('device/register', [DisnewMobileApiController::class, 'registerDevice'])->name('device.register');
Route::post('sync/push', [DisnewMobileApiController::class, 'push'])->name('sync.push');
Route::get('sync/pull', [DisnewMobileApiController::class, 'pull'])->name('sync.pull');
