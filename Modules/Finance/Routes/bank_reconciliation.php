<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\BankReconciliationController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance/bank-reconciliation')
    ->name('finance.bank-reconciliation.')
    ->group(function () {
        Route::get('/', [BankReconciliationController::class, 'index'])->name('index');
        Route::get('/create', [BankReconciliationController::class, 'create'])->name('create');
        Route::get('/transactions', [BankReconciliationController::class, 'transactions'])->name('transactions');
        Route::post('/setup', [BankReconciliationController::class, 'setup'])->name('setup');
        Route::post('/', [BankReconciliationController::class, 'store'])->name('store');
        Route::get('/{id}', [BankReconciliationController::class, 'show'])->where('id', '[0-9]+')->name('show');
        Route::get('/{id}/edit', [BankReconciliationController::class, 'edit'])->where('id', '[0-9]+')->name('edit');
        Route::put('/{id}', [BankReconciliationController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::post('/{id}/finalize', [BankReconciliationController::class, 'finalize'])->where('id', '[0-9]+')->name('finalize');
        Route::post('/{id}/reopen', [BankReconciliationController::class, 'reopen'])->where('id', '[0-9]+')->name('reopen');
        Route::delete('/{id}', [BankReconciliationController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
    });


// Compatibility entry points for installations whose Finance navigation still
// uses the long-standing /accounting-module URL family. These are deliberately
// unnamed so the canonical finance.bank-reconciliation.* route names remain unique.
Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->group(function () {
        Route::get('accounting-module/bank-reconciliation', [BankReconciliationController::class, 'index']);
        Route::get('accounting-module/bank-reconciliation/create', [BankReconciliationController::class, 'create']);
    });
