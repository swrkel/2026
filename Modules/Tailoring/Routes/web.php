<?php
use Illuminate\Support\Facades\Route;
use Modules\Tailoring\Http\Controllers\TailoringReportController;
use Modules\Tailoring\Http\Controllers\TailoringCustomerPortalController;
use Modules\Tailoring\Http\Controllers\TailoringQrTrackingController;

Route::middleware(['web', 'auth'])->prefix('tailoring')->group(function () {
    Route::get('reports/dashboard', [TailoringReportController::class, 'dashboard'])->name('tailoring.reports.dashboard');
    Route::get('reports/production', [TailoringReportController::class, 'production'])->name('tailoring.reports.production');
    Route::get('reports/employee', [TailoringReportController::class, 'employee'])->name('tailoring.reports.employee');
    Route::get('reports/material', [TailoringReportController::class, 'material'])->name('tailoring.reports.material');
    Route::get('reports/customer', [TailoringReportController::class, 'customer'])->name('tailoring.reports.customer');
    Route::get('customer-portal', [TailoringCustomerPortalController::class, 'index'])->name('tailoring.customer_portal.index');
    Route::get('customer-portal/orders/{id}', [TailoringCustomerPortalController::class, 'orderTracking'])->name('tailoring.customer_portal.order_tracking');
    Route::get('qr/job-card/{code}', [TailoringQrTrackingController::class, 'jobCard'])->name('tailoring.qr.job_card');
});
