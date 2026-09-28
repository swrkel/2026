<?php

use Illuminate\Support\Facades\Route;
use Modules\SettlementSW\Http\Controllers\SettlementSwReportController;

Route::get('/reports/settlements', [SettlementSwReportController::class, 'settlements'])->name('settlement-sw.reports.settlements');
Route::get('/reports/settlements/print/{id}', [SettlementSwReportController::class, 'print'])->name('settlement-sw.reports.settlements.print');
