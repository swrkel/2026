<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\DisnewServerTestingFixController;

Route::middleware(['web', 'auth'])->prefix('distribution-new')->name('distribution-new.')->group(function () {
    Route::get('/server-testing/fix-pack-2', [DisnewServerTestingFixController::class, 'index'])->name('server-testing.fix-pack-2');
    Route::get('/server-testing/fix-pack-2/json', [DisnewServerTestingFixController::class, 'json'])->name('server-testing.fix-pack-2.json');
});
