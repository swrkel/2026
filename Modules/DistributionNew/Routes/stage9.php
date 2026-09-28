<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\DisnewApprovalController;

Route::prefix('distribution-new')->middleware(['web','auth'])->group(function () {
    Route::get('approvals', [DisnewApprovalController::class, 'index'])->name('distributionnew.approvals.index');
    Route::post('approvals/{id}/approve', [DisnewApprovalController::class, 'approve'])->name('distributionnew.approvals.approve');
});
