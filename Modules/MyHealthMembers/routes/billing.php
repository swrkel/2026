<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Billing\MyHealthBillingDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Billing\MyHealthBillingInvoiceController;
use Modules\MyHealthMembers\Http\Controllers\Billing\MyHealthBillingPaymentController;
use Modules\MyHealthMembers\Http\Controllers\Billing\MyHealthBillingServiceController;
use Modules\MyHealthMembers\Http\Controllers\Billing\MyHealthClaimSettlementController;

Route::prefix('myhealth/billing')->as('myhealth.billing.')->group(function () {
    Route::get('/', [MyHealthBillingDashboardController::class, 'index'])->name('dashboard');

    Route::get('/services', [MyHealthBillingServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [MyHealthBillingServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [MyHealthBillingServiceController::class, 'store'])->name('services.store');

    Route::get('/invoices', [MyHealthBillingInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [MyHealthBillingInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [MyHealthBillingInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [MyHealthBillingInvoiceController::class, 'show'])->name('invoices.show');

    Route::get('/invoices/{invoice}/payments/create', [MyHealthBillingPaymentController::class, 'create'])->name('payments.create');
    Route::post('/invoices/{invoice}/payments', [MyHealthBillingPaymentController::class, 'store'])->name('payments.store');

    Route::get('/claim-settlements', [MyHealthClaimSettlementController::class, 'index'])->name('claims.index');
    Route::get('/claim-settlements/create', [MyHealthClaimSettlementController::class, 'create'])->name('claims.create');
    Route::post('/claim-settlements', [MyHealthClaimSettlementController::class, 'store'])->name('claims.store');
});
