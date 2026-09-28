<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerDashboardController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerOrderController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerInvoiceController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerStatementController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerDeliveryTrackingController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerReturnRequestController;
use Modules\DistributionNew\Http\Controllers\CustomerPortal\CustomerComplaintController;

Route::middleware(['web','auth','tenant','SetSessionData','language','distributionnew.customer.portal'])
    ->prefix('distribution-new/customer')
    ->as('distribution-new.customer.')
    ->group(function () {
        Route::get('dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
        Route::resource('orders', CustomerOrderController::class)->except(['destroy']);
        Route::get('invoices', [CustomerInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices/{invoice}/download', [CustomerInvoiceController::class, 'download'])->name('invoices.download');
        Route::get('statements', [CustomerStatementController::class, 'index'])->name('statements.index');
        Route::get('deliveries', [CustomerDeliveryTrackingController::class, 'index'])->name('deliveries.index');
        Route::get('deliveries/{delivery}', [CustomerDeliveryTrackingController::class, 'show'])->name('deliveries.show');
        Route::resource('return-requests', CustomerReturnRequestController::class)->only(['index','create','store','show']);
        Route::resource('complaints', CustomerComplaintController::class)->only(['index','create','store','show']);
    });
