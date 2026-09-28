<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpensesNew\Http\Controllers\BudgetController;
use Modules\ExpensesNew\Http\Controllers\PolicyController;
use Modules\ExpensesNew\Http\Controllers\TaxController;
use Modules\ExpensesNew\Http\Controllers\RecurringExpenseController;
use Modules\ExpensesNew\Http\Controllers\AnalyticsController;
use Modules\ExpensesNew\Http\Controllers\BudgetControlController;
use Modules\ExpensesNew\Http\Controllers\VendorAnalyticsController;

Route::prefix('expenses-new')->name('expenses-new.')->middleware(['web','auth'])->group(function () {
    Route::resource('budgets', BudgetController::class);
    Route::resource('policies', PolicyController::class);
    Route::resource('taxes', TaxController::class);
    Route::resource('recurring', RecurringExpenseController::class);
    Route::resource('budget-control', BudgetControlController::class);
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('vendor-analytics', [VendorAnalyticsController::class, 'index'])->name('vendor-analytics.index');
});
