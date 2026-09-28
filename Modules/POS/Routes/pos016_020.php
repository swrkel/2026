<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\AdvancedPaymentController;
use Modules\POS\Http\Controllers\ReceiptPrintController;
use Modules\POS\Http\Controllers\DeviceController;
use Modules\POS\Http\Controllers\EnterpriseMonitorController;

Route::middleware(['web', 'auth'])->prefix('pos-module')->name('pos.')->group(function () {
    // Advanced Sales routes are registered once in Routes/web.php.
    Route::post('advanced-payments/validate', [AdvancedPaymentController::class, 'validatePayments'])->name('advanced_payments.validate');
    Route::post('advanced-payments/reverse', [AdvancedPaymentController::class, 'reverse'])->name('advanced_payments.reverse');
    Route::get('receipts/{sale}/print', [ReceiptPrintController::class, 'print'])->name('receipts.print');
    Route::post('receipts/{sale}/reprint', [ReceiptPrintController::class, 'reprint'])->name('receipts.reprint');
    Route::resource('devices', DeviceController::class)->except(['show']);
    Route::get('enterprise-monitor', [EnterpriseMonitorController::class, 'index'])->name('enterprise_monitor.index');
});
