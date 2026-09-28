<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\AiReplenishmentReviewController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new')->name('stocktransfernew.')->group(function () {
    Route::get('ai-replenishment-review', [AiReplenishmentReviewController::class, 'index'])
        ->name('ai-replenishment.index')
        ->middleware('permission:stocktransfernew.ai_replenishment.view');

    Route::post('ai-replenishment-review/{id}/approve', [AiReplenishmentReviewController::class, 'approve'])
        ->name('ai-replenishment.approve')
        ->middleware('permission:stocktransfernew.ai_replenishment.approve');

    Route::post('ai-replenishment-review/{id}/reject', [AiReplenishmentReviewController::class, 'reject'])
        ->name('ai-replenishment.reject')
        ->middleware('permission:stocktransfernew.ai_replenishment.reject');

    Route::post('ai-replenishment-review/{id}/return', [AiReplenishmentReviewController::class, 'returnForCorrection'])
        ->name('ai-replenishment.return')
        ->middleware('permission:stocktransfernew.ai_replenishment.return');
});
