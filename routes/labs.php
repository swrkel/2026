<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Lab\MyHealthLabController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/lab/{member}', [MyHealthLabController::class, 'index'])->name('lab.index');
    Route::post('/lab/{member}/request', [MyHealthLabController::class, 'storeRequest'])->name('lab.request.store');
    Route::post('/lab/result/{labRequest}', [MyHealthLabController::class, 'storeResult'])->name('lab.result.store');
});
