<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\TransporterScorecardController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/transporter-scorecard')->name('stock-transfer-new.transporter-scorecard.')->group(function () {
    Route::get('/', [TransporterScorecardController::class, 'index'])->name('index');
    Route::get('/export-csv', [TransporterScorecardController::class, 'exportCsv'])->name('export-csv');
});
