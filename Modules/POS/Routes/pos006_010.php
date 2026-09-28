<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\PluginController;
use Modules\POS\Http\Controllers\DiscountController;
use Modules\POS\Http\Controllers\LoyaltyController;
use Modules\POS\Http\Controllers\ReceiptController;
use Modules\POS\Http\Controllers\PaymentController;

Route::middleware(['web', 'auth'])->prefix('pos-module')->name('pos.')->group(function () {
    Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
    Route::get('discounts', [DiscountController::class, 'index'])->name('discounts.index');
    Route::get('loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('receipts/designer', [ReceiptController::class, 'index'])->name('receipts.designer');
    Route::post('payments/multiple', [PaymentController::class, 'store'])->name('payments.multiple.store');
});
