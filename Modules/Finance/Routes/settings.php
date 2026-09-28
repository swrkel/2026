<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\Settings\AccountSettingController;
use Modules\Finance\Http\Controllers\Settings\DefaultAccountController;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
    ->prefix('finance')
    ->name('finance.')
    ->group(function () {
        Route::get('settings', [AccountSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [AccountSettingController::class, 'store'])->name('settings.store');
        Route::get('settings/default-date-range', [AccountSettingController::class, 'getDefaultDateRange'])->name('settings.default-date-range.show');
        Route::post('settings/default-date-range', [AccountSettingController::class, 'saveDefaultDateRange'])->name('settings.default-date-range.store');
        Route::get('settings/{id}/edit', [AccountSettingController::class, 'edit'])->where('id', '[0-9]+')->name('settings.edit');
        Route::put('settings/{id}', [AccountSettingController::class, 'update'])->where('id', '[0-9]+')->name('settings.update');
        Route::delete('settings/{id}', [AccountSettingController::class, 'destroy'])->where('id', '[0-9]+')->name('settings.destroy');
        /*
         * MA-002: the full default-account cycle now lives in Finance.
         *
         * Only 'index' was routed before, but index's DataTable builds its
         * Edit and Delete buttons with action(...), and action() THROWS if no
         * route is registered for that controller method. So moving the
         * controller without these routes would have broken the page rather
         * than fixed it.
         *
         * The paths deliberately mirror core's Route::resource('default-account')
         * so the screens behave identically; they are simply served from
         * /finance/... by the Finance controller.
         */
        Route::any('default-accounts', [DefaultAccountController::class, 'index'])->name('default_accounts.index');
        Route::get('default-accounts/create', [DefaultAccountController::class, 'create'])->name('default_accounts.create');
        Route::post('default-accounts', [DefaultAccountController::class, 'store'])->name('default_accounts.store');
        Route::get('default-accounts/{id}', [DefaultAccountController::class, 'show'])->where('id', '[0-9]+')->name('default_accounts.show');
        Route::get('default-accounts/{id}/edit', [DefaultAccountController::class, 'edit'])->where('id', '[0-9]+')->name('default_accounts.edit');
        Route::put('default-accounts/{id}', [DefaultAccountController::class, 'update'])->where('id', '[0-9]+')->name('default_accounts.update');
        Route::delete('default-accounts/{id}', [DefaultAccountController::class, 'destroy'])->where('id', '[0-9]+')->name('default_accounts.destroy');
    });
