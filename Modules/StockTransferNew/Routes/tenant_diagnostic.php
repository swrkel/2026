<?php

use Illuminate\Support\Facades\Route;

Route::get('/stock-transfer-new/tenant-diagnostic', [\Modules\StockTransferNew\Http\Controllers\RouteClosures\TenantDiagnosticRouteController::class, 'handle1'])->name('stock-transfer-new.tenant-diagnostic');
