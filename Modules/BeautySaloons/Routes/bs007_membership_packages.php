<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\MembershipController;
use Modules\BeautySaloons\Http\Controllers\PackageController;

Route::group(['prefix' => 'beauty-saloons', 'middleware' => ['web', 'auth']], function () {
    Route::resource('memberships', MembershipController::class);
    Route::resource('packages', PackageController::class);
    Route::get('reports/membership-summary', [MembershipController::class, 'report'])->name('beauty-saloons.memberships.report');
    Route::get('reports/package-consumption', [PackageController::class, 'report'])->name('beauty-saloons.packages.report');
});
