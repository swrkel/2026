<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\GiftVoucher\RestaurantGiftVoucherController;

Route::middleware(['web', 'auth', 'restaurantnew.enabled'])->prefix('restaurant-new/gift-vouchers')->name('restaurant-new.gift-vouchers.')->group(function () {
    Route::get('/', [RestaurantGiftVoucherController::class, 'index'])->name('index');
    Route::get('/create', [RestaurantGiftVoucherController::class, 'create'])->name('create');
    Route::post('/', [RestaurantGiftVoucherController::class, 'store'])->name('store');
    Route::get('/{gift_voucher}', [RestaurantGiftVoucherController::class, 'show'])->name('show');
    Route::post('/{gift_voucher}/redeem', [RestaurantGiftVoucherController::class, 'redeem'])->name('redeem');
});
