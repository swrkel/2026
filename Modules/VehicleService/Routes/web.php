<?php

use Illuminate\Support\Facades\Route;
use Modules\VehicleService\Http\Controllers\VehicleServiceController;

Route::group([
    'middleware' => ['web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context'],
    'prefix' => 'vehicle-service',
], function () {
    Route::get('/', [VehicleServiceController::class, 'index'])->name('vehicleservice.jobs.index');
    Route::get('/jobs', [VehicleServiceController::class, 'index'])->name('vehicleservice.jobs.list');
    Route::get('/jobs/create', [VehicleServiceController::class, 'create'])->name('vehicleservice.jobs.create');
    Route::post('/jobs', [VehicleServiceController::class, 'store'])->name('vehicleservice.jobs.store');
    Route::get('/jobs/{id}', [VehicleServiceController::class, 'show'])->whereNumber('id')->name('vehicleservice.jobs.show');
    Route::get('/products/search', [VehicleServiceController::class, 'productSearch'])->name('vehicleservice.products.search');
});
