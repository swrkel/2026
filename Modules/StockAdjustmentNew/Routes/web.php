<?php

use Illuminate\Support\Facades\Route;
use Modules\StockAdjustmentNew\Http\Controllers\AssetController;
use Modules\StockAdjustmentNew\Http\Controllers\BatchLookupController;
use Modules\StockAdjustmentNew\Http\Controllers\DashboardController;
use Modules\StockAdjustmentNew\Http\Controllers\ProductLookupController;
use Modules\StockAdjustmentNew\Http\Controllers\StockAdjustmentController;
use Modules\StockAdjustmentNew\Http\Controllers\ReasonController;
use Modules\StockAdjustmentNew\Http\Controllers\ReportController;
use Modules\StockAdjustmentNew\Http\Controllers\SettingsController;

Route::get('/assets/{type}/{file}', AssetController::class)
    ->where(['type' => 'css|js', 'file' => '[A-Za-z0-9._-]+'])
    ->name('assets');

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

Route::get('/product-lookup', ProductLookupController::class)->name('product-lookup.search');
Route::get('/batch-lookup', BatchLookupController::class)->name('batch-lookup.search');

Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
Route::get('/adjustments/{adjustment}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
Route::post('/adjustments/{adjustment}/submit', [StockAdjustmentController::class, 'submit'])->name('adjustments.submit');
Route::post('/adjustments/{adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
Route::post('/adjustments/{adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');
Route::post('/adjustments/{adjustment}/post', [StockAdjustmentController::class, 'post'])->name('adjustments.post');


Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'updateGeneral'])->name('settings.general.update');
Route::post('/settings/account-mappings', [SettingsController::class, 'storeMapping'])->name('settings.mappings.store');
Route::put('/settings/account-mappings/{mapping}', [SettingsController::class, 'updateMapping'])->name('settings.mappings.update');

Route::resource('reasons', ReasonController::class)->except(['show']);
Route::get('/reports/register', [ReportController::class, 'register'])->name('reports.register');
Route::get('/reports/variance', [ReportController::class, 'variance'])->name('reports.variance');
