<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\MaterialIssueController;
use Modules\Ran\Http\Controllers\ProductionController;
use Modules\Ran\Http\Controllers\ProductionReceiptController;
use Modules\Ran\Http\Controllers\WastageController;

Route::prefix('ran')->as('ran.')->middleware(['web', 'auth', 'ran.enabled'])->group(function (): void {
    Route::resource('production', ProductionController::class)->only(['index', 'show'])
        ->middleware('ran.page:ran.production.view');
    Route::resource('production', ProductionController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.production.create');
    Route::resource('production', ProductionController::class)->only(['edit', 'update'])
        ->middleware('ran.page:ran.production.edit');
    Route::post('production/{production}/approve', [ProductionController::class, 'approve'])
        ->middleware('ran.page:ran.production.approve')->name('production.approve');
    Route::post('production/{production}/material-issues', [MaterialIssueController::class, 'store'])
        ->middleware('ran.page:ran.production.issue_material')->name('production.material-issues.store');
    Route::post('production/{production}/receipts', [ProductionReceiptController::class, 'store'])
        ->middleware('ran.page:ran.production.receive')->name('production.receipts.store');
    Route::post('production/{production}/wastage', [WastageController::class, 'store'])
        ->middleware('ran.page:ran.production.manage_wastage')->name('production.wastage.store');
});
