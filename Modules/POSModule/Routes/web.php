<?php

use Illuminate\Support\Facades\Route;
use Modules\POSModule\Http\Controllers\POSModuleController;

Route::prefix('pos-module')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [POSModuleController::class, 'index'])->name('posmodule.index');
    Route::get('/dashboard', [POSModuleController::class, 'index'])->name('posmodule.dashboard');
});
