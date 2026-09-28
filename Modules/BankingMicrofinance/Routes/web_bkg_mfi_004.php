<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingMicrofinance\Http\Controllers\Field\FieldDashboardController;
use Modules\BankingMicrofinance\Http\Controllers\Field\FieldRoutePlanController;
use Modules\BankingMicrofinance\Http\Controllers\Field\CenterCollectionSheetController;
use Modules\BankingMicrofinance\Http\Controllers\Field\OfflineCollectionBatchController;
use Modules\BankingMicrofinance\Http\Controllers\Field\ReceiptVerificationController;
use Modules\BankingMicrofinance\Http\Controllers\Field\CashHandoverController;
use Modules\BankingMicrofinance\Http\Controllers\Reports\FieldCollectionReportController;

Route::middleware(['web', 'auth'])->prefix('banking/microfinance/field')->name('bkg.mfi.field.')->group(function () {
    Route::get('/', [FieldDashboardController::class, 'index'])->name('dashboard');

    Route::resource('route-plans', FieldRoutePlanController::class)->except(['show']);
    Route::resource('collection-sheets', CenterCollectionSheetController::class)->except(['show']);

    Route::get('offline-batches', [OfflineCollectionBatchController::class, 'index'])->name('offline-batches.index');
    Route::get('offline-batches/create', [OfflineCollectionBatchController::class, 'create'])->name('offline-batches.create');
    Route::post('offline-batches', [OfflineCollectionBatchController::class, 'store'])->name('offline-batches.store');
    Route::post('offline-batches/{batch}/approve', [OfflineCollectionBatchController::class, 'approve'])->name('offline-batches.approve');
    Route::post('offline-batches/{batch}/reject', [OfflineCollectionBatchController::class, 'reject'])->name('offline-batches.reject');

    Route::get('receipt-verification', [ReceiptVerificationController::class, 'index'])->name('receipt-verification.index');
    Route::post('receipt-verification/{receipt}/verify', [ReceiptVerificationController::class, 'verify'])->name('receipt-verification.verify');
    Route::post('receipt-verification/{receipt}/flag', [ReceiptVerificationController::class, 'flag'])->name('receipt-verification.flag');

    Route::resource('cash-handovers', CashHandoverController::class)->except(['show']);
    Route::post('cash-handovers/{handover}/approve', [CashHandoverController::class, 'approve'])->name('cash-handovers.approve');

    Route::get('reports/daily-collection', [FieldCollectionReportController::class, 'daily'])->name('reports.daily');
    Route::get('reports/officer-productivity', [FieldCollectionReportController::class, 'productivity'])->name('reports.productivity');
    Route::get('reports/receipt-exceptions', [FieldCollectionReportController::class, 'receiptExceptions'])->name('reports.receipt-exceptions');
    Route::get('reports/cash-handover-variance', [FieldCollectionReportController::class, 'cashHandoverVariance'])->name('reports.cash-handover-variance');
});
