<?php

use Illuminate\Support\Facades\Route;
use Modules\ManagementReport\Http\Controllers\PublicShareController;
use Modules\ManagementReport\Http\Middleware\EnsureManagementReportTables;

// Public report links remain tenant-domain links; native Stancl domain tenancy is applied by the provider.
Route::middleware([EnsureManagementReportTables::class])->group(function () {
    Route::get('/management-report/shared/{token}', [PublicShareController::class, 'show'])->name('managementreport.public.show');
    Route::get('/management-report/shared/{token}/pdf', [PublicShareController::class, 'pdf'])->name('managementreport.public.pdf');
});
