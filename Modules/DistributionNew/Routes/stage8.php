<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\DisnewDashboardStage8Controller;
use Modules\DistributionNew\Http\Controllers\DisnewLifecycleController;
use Modules\DistributionNew\Http\Controllers\DisnewTripController;
use Modules\DistributionNew\Http\Controllers\DisnewWarehouseController;
use Modules\DistributionNew\Http\Controllers\DisnewBusinessLimitController;
use Modules\DistributionNew\Http\Controllers\DisnewProofController;

Route::middleware(['web','auth'])->prefix('distribution-new')->name('distributionnew.')->group(function () {
    Route::get('/dashboard-stage8', [DisnewDashboardStage8Controller::class, 'index'])->name('dashboard.stage8');
    Route::post('/sales-orders/{id}/status', [DisnewLifecycleController::class, 'update'])->name('sales-orders.status');
    Route::resource('/trips', DisnewTripController::class)->only(['index','store']);
    Route::resource('/warehouses', DisnewWarehouseController::class)->only(['index','store']);
    Route::post('/delivery-proofs', [DisnewProofController::class, 'store'])->name('delivery-proofs.store');
});
Route::middleware(['web','auth'])->prefix('superadmin/business/{businessId}/distribution-new')->name('superadmin.distributionnew.')->group(function () {
    Route::get('/limits', [DisnewBusinessLimitController::class, 'edit'])->name('limits.edit');
    Route::post('/limits', [DisnewBusinessLimitController::class, 'update'])->name('limits.update');
});
