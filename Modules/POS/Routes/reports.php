<?php

use Illuminate\Support\Facades\Route;
use Modules\POS\Http\Controllers\Reports\POSReportController;

Route::prefix('pos-module/reports')->middleware(['web', 'auth'])->as('pos.reports.')->group(function () {
    Route::get('/', [POSReportController::class, 'index'])->name('index');
    Route::get('/export/{report}', [POSReportController::class, 'export'])->name('export');
});
