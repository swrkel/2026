<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\CustomerController;
use Modules\BeautySaloons\Http\Controllers\CustomerVisitController;

Route::prefix('beauty-saloons')->as('beautysaloons.')->middleware(['web', 'auth'])->group(function () {
    Route::resource('customers', CustomerController::class);
    Route::post('customers/{customer}/visit-notes', [CustomerVisitController::class, 'store'])->name('customers.visit-notes.store');
});
