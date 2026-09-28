<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Journal\JournalController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->group(function () {
        Route::get('/journals/get-account-dropdown-by-type/{id}', [JournalController::class, 'getAccountDropdownByAccountType'])
            ->name('finance.accounting.journal.account-dropdown');
        // IS-Journal: sub types for the chosen Account Type.
        Route::get('/journals/get-account-sub-types/{id}', [JournalController::class, 'getAccountSubTypes'])
            ->name('finance.accounting.journal.account-sub-types');
        Route::get('/journals/get_row', [JournalController::class, 'getRow'])
            ->name('finance.accounting.journal.row');
        Route::resource('/journal', JournalController::class)
            ->names('finance.accounting.journal');
    });

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::get('journal', [JournalController::class, 'index'])->name('journal.index');
        Route::get('journal/create', [JournalController::class, 'create'])->name('journal.create');
    });
