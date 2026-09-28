<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\LoyaltyController;

Route::group(['middleware' => ['web', 'auth'], 'prefix' => 'beauty-saloons/loyalty'], function () {
    Route::get('/', [LoyaltyController::class, 'index'])->name('beautysaloons.loyalty.index');
    Route::get('/tiers', [LoyaltyController::class, 'tiers'])->name('beautysaloons.loyalty.tiers');
    Route::get('/statement/{customerId}', [LoyaltyController::class, 'statement'])->name('beautysaloons.loyalty.statement');
    Route::post('/earn', [LoyaltyController::class, 'earn'])->name('beautysaloons.loyalty.earn');
    Route::post('/redeem', [LoyaltyController::class, 'redeem'])->name('beautysaloons.loyalty.redeem');
});
