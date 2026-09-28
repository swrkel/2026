<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\Api\V1\POSApiController;

Route::prefix('api/v1/pos')->middleware(['api'])->group(function () {
    Route::get('/status', [POSApiController::class, 'status'])->name('pos.api.status');
});
