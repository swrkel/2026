<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('list-deposit-transfer', [\Modules\Finance\Http\Controllers\AccountController::class, 'listDepositTransfer'])->name('list-deposit-transfer');
    Route::any('deposits', [\Modules\Finance\Http\Controllers\Deposits\DepositsController::class, 'index'])->name('deposits.index');
    Route::any('deposit-module', [\Modules\Finance\Http\Controllers\Deposits\DepositModuleController::class, 'addDeposit'])->name('deposit_module.index');
});
