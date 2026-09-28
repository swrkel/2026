<?php
use Illuminate\Support\Facades\Route;
use Modules\HRManager\Http\Controllers\HrAssetController;

Route::prefix('hr-manager/assets')->middleware(['web','auth'])->name('hrmanager.assets.')->group(function(){
    Route::get('/', [HrAssetController::class, 'index'])->name('index');
    Route::post('/assign', [HrAssetController::class, 'assign'])->name('assign');
});

Route::prefix('hr/assets')->middleware(['web','auth'])->name('hr.assets.')->group(function(){
    Route::get('/', [HrAssetController::class, 'index'])->name('dashboard');
    Route::post('/assign', [HrAssetController::class, 'assign'])->name('assign');
});
