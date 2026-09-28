<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Api\LiveOperationsApiController;

Route::middleware(['auth:sanctum'])->prefix('api/distribution-new/live')->group(function () {
    Route::post('/driver-status', [LiveOperationsApiController::class, 'driverStatus']);
    Route::post('/vehicle-location', [LiveOperationsApiController::class, 'vehicleLocation']);
    Route::post('/delivery-timeline', [LiveOperationsApiController::class, 'deliveryTimeline']);
});
