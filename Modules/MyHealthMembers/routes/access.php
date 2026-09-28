<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Access\MyHealthBusinessAccessController;

Route::prefix('my-health/access')->name('myhealth.access.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [MyHealthBusinessAccessController::class, 'index'])->name('index');
    Route::get('/create', [MyHealthBusinessAccessController::class, 'create'])->name('create');
    Route::post('/', [MyHealthBusinessAccessController::class, 'store'])->name('store');
    Route::post('/verify', [MyHealthBusinessAccessController::class, 'verify'])->name('verify');
    Route::post('/{id}/revoke', [MyHealthBusinessAccessController::class, 'revoke'])->name('revoke');
});
