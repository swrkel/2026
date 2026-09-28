<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\Reports\BeautyReportsController;

Route::middleware(['web', 'auth'])
    ->prefix(config('beautysaloons.route_prefix', 'beauty-saloons') . '/reports-bi')
    ->name('beautysaloons.reports-bi.')
    ->group(function () {
        Route::get('/', [BeautyReportsController::class, 'dashboard'])->name('dashboard');
        Route::get('/appointments', [BeautyReportsController::class, 'appointments'])->name('appointments');
        Route::get('/sales', [BeautyReportsController::class, 'sales'])->name('sales');
        Route::get('/retail-sales', [BeautyReportsController::class, 'retailSales'])->name('retail-sales');
        Route::get('/customers', [BeautyReportsController::class, 'customers'])->name('customers');
        Route::get('/staff', [BeautyReportsController::class, 'staff'])->name('staff');
        Route::get('/commissions', [BeautyReportsController::class, 'commissions'])->name('commissions');
        Route::get('/inventory', [BeautyReportsController::class, 'inventory'])->name('inventory');
        Route::get('/memberships', [BeautyReportsController::class, 'memberships'])->name('memberships');
        Route::get('/vouchers', [BeautyReportsController::class, 'vouchers'])->name('vouchers');
        Route::get('/loyalty', [BeautyReportsController::class, 'loyalty'])->name('loyalty');
        Route::get('/payments', [BeautyReportsController::class, 'payments'])->name('payments');
    });
