<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomerStatements\Http\Controllers\CustomerStatementController;
use Modules\CustomerStatements\Http\Controllers\CustomerStatementNumberingController;
use Modules\CustomerStatements\Http\Controllers\DataController;

/*
|--------------------------------------------------------------------------
| Customer Statements Workspace Routes
|--------------------------------------------------------------------------
*/
Route::get('/', [CustomerStatementController::class, 'index'])->name('index');
Route::get('/dashboard', [CustomerStatementController::class, 'index'])->name('dashboard');
Route::get('/create', [CustomerStatementController::class, 'index'])->name('create');
Route::get('/list', [CustomerStatementController::class, 'index'])->name('list');
Route::get('/statement-settings', [CustomerStatementController::class, 'index'])->name('statement-settings');
Route::get('/settings', [CustomerStatementController::class, 'index'])->name('settings');
Route::get('/payments', [CustomerStatementController::class, 'index'])->name('payments');
Route::get('/font-settings', [CustomerStatementController::class, 'index'])->name('font-settings');
Route::get('/print-formats', [CustomerStatementController::class, 'index'])->name('print-formats');
Route::get('/alert-settings', [CustomerStatementController::class, 'index'])->name('alert-settings');

/* Module-owned data endpoints. They delegate to the existing Customers engine
 * and only correct bill-date display, location/number persistence, and session scope.
 */
Route::get('/data/bills', [DataController::class, 'billList'])
    ->name('data.bills.list');
Route::post('/data/statements', [DataController::class, 'store'])
    ->name('data.statements.store');
Route::get('/data/statements', [DataController::class, 'statementList'])
    ->name('data.statements.list');
Route::get('/data/numbering-settings', [DataController::class, 'numberingSettings'])
    ->name('data.numbering-settings.list');

Route::get('/numbering-settings', [CustomerStatementNumberingController::class, 'show'])
    ->name('numbering-settings.show');
Route::post('/numbering-settings', [CustomerStatementNumberingController::class, 'store'])
    ->name('numbering-settings.store');
Route::delete('/numbering-settings/{setting}', [CustomerStatementNumberingController::class, 'destroy'])
    ->whereNumber('setting')
    ->name('numbering-settings.destroy');
Route::get('/numbering-settings/next', [CustomerStatementNumberingController::class, 'next'])
    ->name('numbering-settings.next');
