<?php
use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\FinanceController;

Route::prefix('beauty-saloons/finance')->middleware(['web','auth'])->group(function () {
    Route::get('/mappings', [FinanceController::class, 'mappings'])->name('beauty-saloons.finance.mappings');
    Route::post('/mappings', [FinanceController::class, 'saveMappings'])->name('beauty-saloons.finance.mappings.save');
    Route::get('/postings', [FinanceController::class, 'postings'])->name('beauty-saloons.finance.postings');
});
