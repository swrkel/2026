<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Api\DisnewScannerApiController;

Route::middleware(['api'])->prefix('api/distribution-new/scanner')->group(function(){
    Route::post('/scan', [DisnewScannerApiController::class,'scan']);
});
