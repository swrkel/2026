<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\MultiBranchOperationsController;

Route::group(['prefix' => 'restaurant-new/multi-branch', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', [MultiBranchOperationsController::class, 'dashboard'])->name('restaurant-new.multi-branch.dashboard');
    Route::get('/transfers', [MultiBranchOperationsController::class, 'transfers'])->name('restaurant-new.multi-branch.transfers');
    Route::get('/comparison', [MultiBranchOperationsController::class, 'comparison'])->name('restaurant-new.multi-branch.comparison');
    Route::post('/transfers/{transfer}/approve', [MultiBranchOperationsController::class, 'approve'])->name('restaurant-new.multi-branch.transfers.approve');
    Route::post('/transfers/{transfer}/dispatch', [MultiBranchOperationsController::class, 'dispatch'])->name('restaurant-new.multi-branch.transfers.dispatch');
    Route::post('/transfers/{transfer}/receive', [MultiBranchOperationsController::class, 'receive'])->name('restaurant-new.multi-branch.transfers.receive');
});
