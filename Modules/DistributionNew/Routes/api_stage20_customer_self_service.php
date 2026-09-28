<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Api\CustomerSelfServiceApiController;

Route::middleware(['api','auth:sanctum','tenant'])
    ->prefix('api/distribution-new/customer')
    ->as('api.distribution-new.customer.')
    ->group(function () {
        Route::get('dashboard', [CustomerSelfServiceApiController::class, 'dashboard']);
        Route::get('orders', [CustomerSelfServiceApiController::class, 'orders']);
        Route::post('orders', [CustomerSelfServiceApiController::class, 'storeOrder']);
        Route::get('invoices', [CustomerSelfServiceApiController::class, 'invoices']);
        Route::get('deliveries', [CustomerSelfServiceApiController::class, 'deliveries']);
        Route::post('return-requests', [CustomerSelfServiceApiController::class, 'storeReturnRequest']);
        Route::post('complaints', [CustomerSelfServiceApiController::class, 'storeComplaint']);
    });
