<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    Route::any('accounts', [\Modules\Finance\Http\Controllers\AccountController::class, 'index'])->name('accounts.index');
    Route::any('accounts/create', [\Modules\Finance\Http\Controllers\AccountController::class, 'create'])->name('accounts.create');
    Route::any('account-groups', [\Modules\Finance\Http\Controllers\Accounts\AccountGroupController::class, 'index'])->name('account_groups.index');
    Route::any('account-types', [\Modules\Finance\Http\Controllers\Accounts\AccountTypeController::class, 'index'])->name('account_types.index');
});
