<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Scanner\DisnewScannerController;
use Modules\DistributionNew\Http\Controllers\Scanner\DisnewBarcodeController;

Route::middleware(['web','auth'])->prefix('distribution-new/scanner')->name('distribution-new.scanner.')->group(function(){
    Route::get('/', [DisnewScannerController::class,'index'])->name('index');
    Route::get('/loading', [DisnewScannerController::class,'loading'])->name('loading');
    Route::get('/unloading', [DisnewScannerController::class,'unloading'])->name('unloading');
    Route::get('/bins', [DisnewScannerController::class,'bins'])->name('bins');
    Route::get('/verify', [DisnewScannerController::class,'verify'])->name('verify');
    Route::post('/scan', [DisnewScannerController::class,'scan'])->name('scan');
    Route::get('/labels', [DisnewBarcodeController::class,'labels'])->name('labels');
    Route::post('/labels', [DisnewBarcodeController::class,'store'])->name('labels.store');
});
