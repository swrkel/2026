<?php

use Illuminate\Support\Facades\Route;
use Modules\DailyCollectionSW\Http\Controllers\DailyCollectionSWController;

Route::middleware([
        'auth',
        'language',
        'SetSessionData',
        'DayEnd',
        'tenant.context',
    ])
    ->prefix('dailycollectionsw')
    ->name('dailycollectionsw.')
    ->group(function () {
        Route::get('/daily-collection-sw/print/{pump_operator_id}', [DailyCollectionSWController::class, 'print'])
            ->name('daily-collection-sw.print');
        Route::get('/daily-collection-sw/edit-shortage/{id}', [DailyCollectionSWController::class, 'editShortage'])
            ->name('daily-collection-sw.edit-shortage');
        Route::put('/daily-collection-sw/edit-shortage/{id}', [DailyCollectionSWController::class, 'updateShortage'])
            ->name('daily-collection-sw.update-shortage');
        Route::get('/daily-collection-sw/get-balance-collection/{pump_operator_id}', [DailyCollectionSWController::class, 'getBalanceCollection'])
            ->name('daily-collection-sw.balance');
        Route::get('/daily-collection-sw/get-daily-shift/{pump_operator_id}', [DailyCollectionSWController::class, 'getByOperator'])
            ->name('daily-collection-sw.shifts-by-operator');
        Route::get('/daily-collection-sw/daily-shift-options', [DailyCollectionSWController::class, 'getDailyShiftOptions'])
            ->name('daily-collection-sw.shift-options');
        Route::get('/daily-collection-sw/product-price', [DailyCollectionSWController::class, 'getProductPrice'])
            ->name('daily-collection-sw.product-price');
        Route::get('/daily-collection-sw/customer-details/{customer_id}', [DailyCollectionSWController::class, 'getCustomerDetails'])
            ->name('daily-collection-sw.customer-details');
        Route::get('/daily-collection-sw/modal/daily-cash', [DailyCollectionSWController::class, 'create'])
            ->name('daily-collection-sw.modal.daily-cash');
        Route::get('/daily-collection-sw/modal/daily-cards', [DailyCollectionSWController::class, 'createDailyCardsModal'])
            ->name('daily-collection-sw.modal.daily-cards');
        Route::get('/daily-collection-sw/modal/credit-sales', [DailyCollectionSWController::class, 'createCreditSalesModal'])
            ->name('daily-collection-sw.modal.credit-sales');
        Route::get('/daily-collection-sw/summary', [DailyCollectionSWController::class, 'collectionSummary'])
            ->name('daily-collection-sw.summary');
        Route::get('/daily-collection-sw/shortage-excess', [DailyCollectionSWController::class, 'indexShortageExcess'])
            ->name('daily-collection-sw.shortage-excess');
        Route::delete('/daily-collection-sw/shortage-excess/{id}', [DailyCollectionSWController::class, 'destroyShortageExcess'])
            ->name('daily-collection-sw.shortage-excess.destroy');
        Route::get('/daily-collection-sw/cheques', [DailyCollectionSWController::class, 'indexCheque'])
            ->name('daily-collection-sw.cheques');
        Route::get('/daily-collection-sw/others', [DailyCollectionSWController::class, 'indexOther'])
            ->name('daily-collection-sw.others');

        Route::post('/daily-cards', [DailyCollectionSWController::class, 'storeDailyCard'])->name('daily-cards.store');
        Route::put('/daily-cards/{id}', [DailyCollectionSWController::class, 'updateDailyCard'])->name('daily-cards.update');
        Route::post('/daily-vouchers', [DailyCollectionSWController::class, 'storeDailyVoucher'])->name('daily-vouchers.store');
        Route::get('/check-slip-no', [DailyCollectionSWController::class, 'checkSlipNo'])->name('check-slip-no');
        Route::post('/daily-shifts/open', [DailyCollectionSWController::class, 'openDailyShift'])->name('daily-shifts.open');
        Route::post('/daily-shifts', [DailyCollectionSWController::class, 'storeDailyShift'])->name('daily-shifts.store');
        Route::post('/daily-shifts/save', [DailyCollectionSWController::class, 'saveDailyShift'])->name('daily-shifts.save');
        Route::get('/daily-shifts/open', [DailyCollectionSWController::class, 'fetchOpenDailyShift'])->name('daily-shifts.fetch-open');

        Route::get('/settings', [DailyCollectionSWController::class, 'settings'])->name('settings');
        Route::post('/settings', [DailyCollectionSWController::class, 'saveSettings'])->name('settings.save');
        Route::get('/daily-cash-status', [DailyCollectionSWController::class, 'getDailyCashStatus'])->name('daily-cash-status');
        Route::get('/daily-cash-status-data', [DailyCollectionSWController::class, 'getDailyCashStatusData'])->name('daily-cash-status.data');
        Route::get('/daily-cash-status-data-by-date', [DailyCollectionSWController::class, 'getByDate'])->name('daily-cash-status.by-date');
        Route::post('/daily-shift-status/close', [DailyCollectionSWController::class, 'shiftcloseStatus'])->name('daily-shift-status.close');

        Route::resource('/daily-collection-sw', DailyCollectionSWController::class)->except(['show']);
    });

Route::middleware([
        'auth',
        'language',
        'SetSessionData',
        'DayEnd',
        'tenant.context',
    ])
    ->group(function () {
        Route::get('get-settings-sw', [DailyCollectionSWController::class, 'settings'])->name('petro.getSettings.sw');
        Route::post('save-settings-sw', [DailyCollectionSWController::class, 'saveSettings'])->name('daily_collection.save_settings_sw');
        Route::get('daily-cash-status-sw', [DailyCollectionSWController::class, 'getDailyCashStatus'])->name('daily.cash.status.sw');
        Route::get('daily-cash-status-data-sw', [DailyCollectionSWController::class, 'getDailyCashStatusData'])->name('daily.cash.status.datasw');
        Route::get('daily-cash-status-data-by-date-sw', [DailyCollectionSWController::class, 'getByDate'])->name('daily.cash.status.data.by.date.sw');
        Route::post('daily_shift_status.close-sw', [DailyCollectionSWController::class, 'shiftcloseStatus'])->name('daily_shift_status.close.sw');
    });

