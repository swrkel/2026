<?php

use Illuminate\Support\Facades\Route;
use Modules\PumperDashboardNew\Http\Controllers\Auth\OperatorLoginController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\CollectionController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\DashboardController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\DayEntryController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\DocumentController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\LedgerController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\OtherSaleController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\PasscodeController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\PaymentController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\PumpController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\ReconciliationController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\ReportController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\ShiftController;
use Modules\PumperDashboardNew\Http\Controllers\Operator\UnloadStockController;

Route::prefix('operator')->name('operator.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    Route::prefix('pumps')->name('pumps.')->group(function (): void {
        Route::get('/', [PumpController::class, 'index'])->name('index');
        Route::get('/{assignment}/history', [PumpController::class, 'history'])->whereNumber('assignment')->name('history');
        Route::get('/closed-statement', [PumpController::class, 'closedStatement'])->name('closed-statement');
        Route::post('/{assignment}/accept', [PumpController::class, 'accept'])->whereNumber('assignment')->name('accept');
        Route::post('/{assignment}/confirm', [PumpController::class, 'confirm'])->whereNumber('assignment')->name('confirm');
        Route::get('/{assignment}/current-meter', [PumpController::class, 'current'])->whereNumber('assignment')->name('current');
        Route::post('/{assignment}/current-meter', [PumpController::class, 'storeCurrent'])->whereNumber('assignment')->name('current.store');
        Route::get('/{assignment}/close', [PumpController::class, 'closing'])->whereNumber('assignment')->name('close.form');
        Route::post('/{assignment}/close', [PumpController::class, 'close'])->whereNumber('assignment')->name('close');
    });

    Route::prefix('payments')->name('payments.')->group(function (): void {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/summary', [PaymentController::class, 'summary'])->name('summary');
        Route::get('/{payment}/print', [PaymentController::class, 'print'])->whereNumber('payment')->name('print');
        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->whereNumber('payment')->name('edit');
        Route::put('/{payment}', [PaymentController::class, 'update'])->whereNumber('payment')->name('update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->whereNumber('payment')->name('destroy');
        Route::get('/{payment}', [PaymentController::class, 'show'])->whereNumber('payment')->name('show');
    });

    Route::prefix('other-sales')->name('other-sales.')->group(function (): void {
        Route::get('/', [OtherSaleController::class, 'index'])->name('index');
        Route::get('/create', [OtherSaleController::class, 'create'])->name('create');
        Route::post('/', [OtherSaleController::class, 'store'])->name('store');
        Route::get('/{sale}/print', [OtherSaleController::class, 'print'])->whereNumber('sale')->name('print');
        Route::get('/{sale}/edit', [OtherSaleController::class, 'edit'])->whereNumber('sale')->name('edit');
        Route::put('/{sale}', [OtherSaleController::class, 'update'])->whereNumber('sale')->name('update');
        Route::delete('/{sale}', [OtherSaleController::class, 'destroy'])->whereNumber('sale')->name('destroy');
        Route::get('/{sale}', [OtherSaleController::class, 'show'])->whereNumber('sale')->name('show');
    });

    Route::prefix('unload-stock')->name('unload-stock.')->group(function (): void {
        Route::get('/', [UnloadStockController::class, 'index'])->name('index');
        Route::get('/create', [UnloadStockController::class, 'create'])->name('create');
        Route::post('/', [UnloadStockController::class, 'store'])->name('store');
        Route::get('/{unload}/print', [UnloadStockController::class, 'print'])->whereNumber('unload')->name('print');
        Route::get('/{unload}/edit', [UnloadStockController::class, 'edit'])->whereNumber('unload')->name('edit');
        Route::put('/{unload}', [UnloadStockController::class, 'update'])->whereNumber('unload')->name('update');
        Route::delete('/{unload}', [UnloadStockController::class, 'destroy'])->whereNumber('unload')->name('destroy');
        Route::get('/{unload}', [UnloadStockController::class, 'show'])->whereNumber('unload')->name('show');
    });

    Route::prefix('day-entries')->name('day-entries.')->group(function (): void {
        Route::get('/', [DayEntryController::class, 'index'])->name('index');
        Route::post('/', [DayEntryController::class, 'store'])->name('store');
        Route::get('/{entry}/edit', [DayEntryController::class, 'edit'])->whereNumber('entry')->name('edit');
        Route::put('/{entry}', [DayEntryController::class, 'update'])->whereNumber('entry')->name('update');
        Route::delete('/{entry}', [DayEntryController::class, 'destroy'])->whereNumber('entry')->name('destroy');
    });

    Route::prefix('collections')->name('collections.')->group(function (): void {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::post('/', [CollectionController::class, 'store'])->name('store');
        Route::get('/{collection}/print', [CollectionController::class, 'print'])->whereNumber('collection')->name('print');
        Route::delete('/{collection}', [CollectionController::class, 'destroy'])->whereNumber('collection')->name('destroy');
        Route::get('/{collection}', [CollectionController::class, 'show'])->whereNumber('collection')->name('show');
    });

    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');

    Route::prefix('reconciliation')->name('reconciliation.')->group(function (): void {
        Route::get('/', [ReconciliationController::class, 'index'])->name('index');
        Route::post('/recover-shortage', [ReconciliationController::class, 'recover'])->name('recover');
        Route::post('/excess-commission', [ReconciliationController::class, 'commission'])->name('commission');
        Route::delete('/recoveries/{recovery}', [ReconciliationController::class, 'voidRecovery'])->whereNumber('recovery')->name('recoveries.destroy');
        Route::delete('/commissions/{commission}', [ReconciliationController::class, 'voidCommission'])->whereNumber('commission')->name('commissions.destroy');
    });

    Route::prefix('documents')->name('documents.')->group(function (): void {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/notes', [DocumentController::class, 'addNote'])->name('notes.store');
        Route::delete('/notes/{note}', [DocumentController::class, 'archive'])->whereNumber('note')->name('notes.archive');
        Route::post('/upload', [DocumentController::class, 'upload'])->name('upload');
        Route::get('/{document}/download', [DocumentController::class, 'download'])->whereNumber('document')->name('download');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->whereNumber('document')->name('destroy');
    });

    Route::get('/settings/passcode', [PasscodeController::class, 'edit'])->name('settings.passcode.edit');
    Route::put('/settings/passcode', [PasscodeController::class, 'update'])->name('settings.passcode.update');

    Route::get('/shift/summary', [ShiftController::class, 'summary'])->name('shift.summary');
    Route::get('/shift/print', [ShiftController::class, 'print'])->name('shift.print');
    Route::get('/shift/close', [ShiftController::class, 'closeForm'])->name('shift.close.form');
    Route::post('/shift/close', [ShiftController::class, 'close'])->name('shift.close');

    Route::get('/reports/meters-with-payments', [ReportController::class, 'metersWithPayments'])->name('reports.meters-with-payments');
    Route::post('/logout', [OperatorLoginController::class, 'logout'])->name('logout');
});
