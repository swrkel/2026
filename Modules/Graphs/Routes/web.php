<?php
use Illuminate\Support\Facades\Route;
use Modules\Graphs\Http\Controllers\GraphsController;

Route::prefix('graphs')->name('graphs.')->group(function () {
    Route::get('/', [GraphsController::class, 'index'])->name('index');
    Route::get('/financial-profitability', [GraphsController::class, 'financial'])->name('financial');
    Route::get('/operational-loss', [GraphsController::class, 'operational'])->name('operational');
    Route::get('/customer-payment', [GraphsController::class, 'customerPayment'])->name('customer-payment');
    Route::get('/management-dashboard', [GraphsController::class, 'managementDashboard'])->name('management-dashboard');

    Route::get('/data/tanks', [GraphsController::class, 'tanks'])->name('data.tanks');
    Route::get('/data/fuel-sales', [GraphsController::class, 'fuelSales'])->name('data.fuel-sales');
    Route::get('/data/non-fuel-sales', [GraphsController::class, 'nonFuelSales'])->name('data.non-fuel-sales');

    Route::get('/data/reconciliation', [GraphsController::class, 'reconciliation'])->name('data.reconciliation');
    Route::get('/data/profitability', [GraphsController::class, 'profitability'])->name('data.profitability');
    Route::get('/data/debtors-ageing/customers', [GraphsController::class, 'debtorsAgeingCustomers'])->name('data.debtors-ageing.customers');
    Route::get('/data/debtors-ageing', [GraphsController::class, 'debtorsAgeing'])->name('data.debtors-ageing');

    Route::get('/data/dip-variance', [GraphsController::class, 'dipVariance'])->name('data.dip-variance');
    Route::get('/data/pump-shift-sales', [GraphsController::class, 'pumpShiftSales'])->name('data.pump-shift-sales');
    Route::get('/data/payment-method-split', [GraphsController::class, 'paymentMethodSplit'])->name('data.payment-method-split');
    Route::get('/data/customer-pump-shift-sales', [GraphsController::class, 'customerPumpShiftSales'])->name('data.customer-pump-shift-sales');
    Route::get('/data/top-credit-customers', [GraphsController::class, 'topCreditCustomers'])->name('data.top-credit-customers');
    Route::get('/data/management-dashboard', [GraphsController::class, 'managementDashboardData'])->name('data.management-dashboard');

    Route::get('/asset/{type}/{file}', [GraphsController::class, 'asset'])
        ->where(['type' => 'css|js|vendor', 'file' => '[A-Za-z0-9._-]+'])
        ->name('asset');
});
