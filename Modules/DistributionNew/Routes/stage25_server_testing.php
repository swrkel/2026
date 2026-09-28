<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\ServerTesting\DisnewServerTestingController;

Route::prefix('distribution-new/server-testing')->name('distributionnew.server-testing.')->group(function () {
    Route::get('/', [DisnewServerTestingController::class, 'index'])->name('index');
    Route::post('/run', [DisnewServerTestingController::class, 'run'])->name('run');
    Route::get('/download-checklist', [DisnewServerTestingController::class, 'downloadChecklist'])->name('download-checklist');
});
