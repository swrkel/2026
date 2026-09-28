<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Settings\MyHealthSettingsController;

Route::prefix('myhealth/settings')->as('myhealth.settings.')->group(function () {
    Route::get('/', [MyHealthSettingsController::class, 'index'])->name('index');
    Route::post('/', [MyHealthSettingsController::class, 'update'])->name('update');
});
