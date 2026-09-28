<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Hardening\IntegrityController;

Route::group(['prefix' => 'restaurant-new/hardening', 'as' => 'restaurantnew.hardening.', 'middleware' => ['web', 'auth', 'restaurantnew.tenant.scope']], function () {
    Route::get('/integrity', [IntegrityController::class, 'index'])->name('integrity.index');
    Route::post('/integrity/run', [IntegrityController::class, 'run'])->name('integrity.run');
    Route::get('/dependencies', [IntegrityController::class, 'dependencyScan'])->name('dependencies');
    Route::get('/scope-logs', [IntegrityController::class, 'scopeLogs'])->name('scope_logs');
});
