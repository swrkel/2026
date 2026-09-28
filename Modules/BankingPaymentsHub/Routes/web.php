<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentsDashboardController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentQueueController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentBatchController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentRouteController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentReconciliationController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentsReportController;
use Modules\BankingPaymentsHub\Http\Controllers\PaymentsSettingController;

Route::middleware(['web', 'auth'])->prefix('banking/payments-hub')->name('banking.payments.')->group(function () {
    Route::get('/', [PaymentsDashboardController::class, 'index'])->name('index');
    Route::get('/queue', [PaymentQueueController::class, 'index'])->name('queue.index');
    Route::get('/batches', [PaymentBatchController::class, 'index'])->name('batches.index');
    Route::get('/routing', [PaymentRouteController::class, 'index'])->name('routing.index');
    Route::get('/reconciliation', [PaymentReconciliationController::class, 'index'])->name('reconciliation.index');
    Route::get('/reports', [PaymentsReportController::class, 'index'])->name('reports.index');
    Route::get('/settings', [PaymentsSettingController::class, 'index'])->name('settings.index');
});
