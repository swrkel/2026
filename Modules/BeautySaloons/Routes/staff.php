<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\StaffController;

Route::prefix('beauty-saloons/staff')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [StaffController::class, 'index'])->name('beauty-saloons.staff.index');
    Route::get('/create', [StaffController::class, 'create'])->name('beauty-saloons.staff.create');
});
