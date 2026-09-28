<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])->prefix('finance')->name('finance.')->group(function () {
    /*
     * MA-002: the expense cycle now lives in Finance. Only 'index' was routed,
     * but index's DataTable builds Edit and Delete links with action(...), and
     * the create/edit views post to @store and @update. action() throws when
     * the target has no route, so these had to be registered alongside.
     */
    Route::any('expenses', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('expenses/create', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('expenses', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('expenses/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'show'])->where('id', '[0-9]+')->name('expenses.show');
    Route::get('expenses/{id}/edit', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'edit'])->where('id', '[0-9]+')->name('expenses.edit');
    Route::put('expenses/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'update'])->where('id', '[0-9]+')->name('expenses.update');
    Route::delete('expenses/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'destroy'])->where('id', '[0-9]+')->name('expenses.destroy');
    Route::get('expenses/{id}/print', [\Modules\Finance\Http\Controllers\Expenses\ExpenseController::class, 'print'])->where('id', '[0-9]+')->name('expenses.print');
    /*
     * MA-002: the full expense-category cycle now lives in Finance.
     *
     * Only 'index' was routed before, but index's DataTable builds View, Edit
     * and Delete buttons with action(...), and the create/edit views post to
     * @store and @update. action() THROWS when no route is registered for the
     * target method, so moving the controller without these routes would have
     * broken the screen rather than fixed it.
     *
     * Paths mirror core's expense-category routes so behaviour is unchanged;
     * they are simply served from /finance/... by Finance's controller.
     *
     * 'view' is deliberately NOT routed - its Blade file does not exist, in
     * core either. See the note on that method in the controller.
     */
    Route::any('expense-categories', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'index'])->name('expense_categories.index');
    Route::get('expense-categories/create', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'create'])->name('expense_categories.create');
    Route::post('expense-categories', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'store'])->name('expense_categories.store');
    Route::get('expense-categories/dropdown', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'getExpenseCategoryDropDown'])->name('expense_categories.dropdown');
    Route::post('expense-categories/check-duplicate', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'checkDuplicate'])->name('expense_categories.check_duplicate');
    Route::get('expense-categories/{id}/account', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'getAccountIdByCategory'])->where('id', '[0-9]+')->name('expense_categories.account');
    Route::get('expense-categories/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'show'])->where('id', '[0-9]+')->name('expense_categories.show');
    Route::get('expense-categories/{id}/edit', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'edit'])->where('id', '[0-9]+')->name('expense_categories.edit');
    Route::put('expense-categories/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'update'])->where('id', '[0-9]+')->name('expense_categories.update');
    Route::delete('expense-categories/{id}', [\Modules\Finance\Http\Controllers\Expenses\ExpenseCategoryController::class, 'destroy'])->where('id', '[0-9]+')->name('expense_categories.destroy');
});
