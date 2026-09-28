<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\ServiceCatalogueController;

Route::prefix('beauty-saloons')->middleware(['web', 'auth'])->group(function () {
    Route::resource('services', ServiceCatalogueController::class)->names('beautysaloons.services');
});
