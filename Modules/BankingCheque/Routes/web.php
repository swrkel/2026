<?php

use Illuminate\Support\Facades\Route;
use Modules\BankingCheque\Http\Controllers\BankingChequeDashboardController;
use Modules\BankingCheque\Http\Controllers\ChequeBookController;
use Modules\BankingCheque\Http\Controllers\ChequeLeafController;
use Modules\BankingCheque\Http\Controllers\StopPaymentController;
use Modules\BankingCheque\Http\Controllers\ClearingController;
use Modules\BankingCheque\Http\Controllers\ReturnedChequeController;
use Modules\BankingCheque\Http\Controllers\ChequeReportController;

Route::middleware(['web','auth'])->prefix('banking/cheques')->name('banking.cheques.')->group(function () {
    Route::get('/', [BankingChequeDashboardController::class, 'index'])->name('dashboard');
    Route::resource('books', ChequeBookController::class);
    Route::resource('leaves', ChequeLeafController::class)->only(['index','show','update']);
    Route::resource('stop-payments', StopPaymentController::class);
    Route::post('clearing/{direction}/batch', [ClearingController::class, 'storeBatch'])->name('clearing.batch.store');
    Route::get('clearing/{direction}', [ClearingController::class, 'index'])->name('clearing.index');
    Route::post('clearing/{direction}/{batch}/approve', [ClearingController::class, 'approve'])->name('clearing.approve');
    Route::resource('returned-cheques', ReturnedChequeController::class);
    Route::get('reports/{report}', [ChequeReportController::class, 'show'])->name('reports.show');
});
