<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpensesNew\Http\Controllers\IntegrationController;

// /api/expenses-new and the API/tenant middleware are applied by the module
// RouteServiceProvider. Keep this file relative to avoid a doubled /api prefix.
Route::post('/post-cost', [IntegrationController::class, 'postCost'])
    ->name('api.expenses-new.post-cost');
