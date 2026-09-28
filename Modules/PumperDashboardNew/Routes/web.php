<?php

use Illuminate\Support\Facades\Route;
use Modules\PumperDashboardNew\Http\Controllers\Admin\AssignmentController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\DashboardController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\DocumentController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\IntegrationController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\LedgerController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\LoginAttemptController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\OperatorController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\PrintLogController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\ReconciliationController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\ReportController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\SettingsController;
use Modules\PumperDashboardNew\Http\Controllers\Admin\ShiftController;

Route::middleware('permission:pumper_dashboard_new.access')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.dashboard.view')->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.dashboard.view')->name('admin.dashboard.index');

    Route::get('/admin/operators', [OperatorController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.operators.view')->name('admin.operators.index');
    Route::post('/admin/operators/sync', [OperatorController::class, 'sync'])
        ->middleware('permission:pumper_dashboard_new.operators.manage')->name('admin.operators.sync');
    Route::put('/admin/operators/{operator}', [OperatorController::class, 'update'])
        ->whereNumber('operator')->middleware('permission:pumper_dashboard_new.operators.manage')->name('admin.operators.update');

    Route::get('/admin/shifts', [ShiftController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.shifts.view')->name('admin.shifts.index');
    Route::get('/admin/shifts/create', [ShiftController::class, 'create'])
        ->middleware('permission:pumper_dashboard_new.shifts.manage')->name('admin.shifts.create');
    Route::post('/admin/shifts', [ShiftController::class, 'store'])
        ->middleware('permission:pumper_dashboard_new.shifts.manage')->name('admin.shifts.store');
    Route::get('/admin/shifts/{shift}', [ShiftController::class, 'show'])
        ->whereNumber('shift')->middleware('permission:pumper_dashboard_new.shifts.view')->name('admin.shifts.show');

    Route::post('/admin/shifts/{shift}/assignments', [AssignmentController::class, 'store'])
        ->whereNumber('shift')->middleware('permission:pumper_dashboard_new.assignments.manage')->name('admin.assignments.store');
    Route::put('/admin/assignments/{assignment}', [AssignmentController::class, 'update'])
        ->whereNumber('assignment')->middleware('permission:pumper_dashboard_new.assignments.manage')->name('admin.assignments.update');
    Route::delete('/admin/assignments/{assignment}', [AssignmentController::class, 'destroy'])
        ->whereNumber('assignment')->middleware('permission:pumper_dashboard_new.assignments.manage')->name('admin.assignments.destroy');

    Route::get('/admin/collections', [ReportController::class, 'index'])
        ->defaults('report', 'collections')->middleware('permission:pumper_dashboard_new.collections.view')->name('admin.collections.index');

    Route::get('/admin/ledger', [LedgerController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.ledger.view')->name('admin.ledger.index');

    Route::get('/admin/reconciliation', [ReconciliationController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.reconciliation.view')->name('admin.reconciliation.index');
    Route::post('/admin/reconciliation/shifts/{shift}/recover', [ReconciliationController::class, 'recover'])
        ->whereNumber('shift')->middleware('permission:pumper_dashboard_new.reconciliation.manage')->name('admin.reconciliation.recover');
    Route::post('/admin/reconciliation/shifts/{shift}/commission', [ReconciliationController::class, 'commission'])
        ->whereNumber('shift')->middleware('permission:pumper_dashboard_new.reconciliation.manage')->name('admin.reconciliation.commission');
    Route::delete('/admin/reconciliation/recoveries/{recovery}', [ReconciliationController::class, 'voidRecovery'])
        ->whereNumber('recovery')->middleware('permission:pumper_dashboard_new.reconciliation.manage')->name('admin.reconciliation.recoveries.destroy');
    Route::delete('/admin/reconciliation/commissions/{commission}', [ReconciliationController::class, 'voidCommission'])
        ->whereNumber('commission')->middleware('permission:pumper_dashboard_new.reconciliation.manage')->name('admin.reconciliation.commissions.destroy');

    Route::get('/admin/documents', [DocumentController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.documents.view')->name('admin.documents.index');
    Route::get('/admin/documents/{document}/download', [DocumentController::class, 'download'])
        ->whereNumber('document')->middleware('permission:pumper_dashboard_new.documents.view')->name('admin.documents.download');

    Route::get('/admin/login-attempts', [LoginAttemptController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.login_attempts.view')->name('admin.login-attempts.index');
    Route::post('/admin/login-attempts/unblock-all', [LoginAttemptController::class, 'unblockAll'])
        ->middleware('permission:pumper_dashboard_new.login_attempts.manage')->name('admin.login-attempts.unblock-all');
    Route::post('/admin/login-attempts/{attempt}/unblock', [LoginAttemptController::class, 'unblock'])
        ->whereNumber('attempt')->middleware('permission:pumper_dashboard_new.login_attempts.manage')->name('admin.login-attempts.unblock');

    Route::get('/admin/print-logs', [PrintLogController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.print_logs.view')->name('admin.print-logs.index');

    Route::get('/admin/reports/{report?}', [ReportController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.reports.view')->name('admin.reports.index');
    Route::get('/admin/reports/{report}/export', [ReportController::class, 'export'])
        ->middleware('permission:pumper_dashboard_new.reports.export')->name('admin.reports.export');

    Route::get('/admin/integration', [IntegrationController::class, 'index'])
        ->middleware('permission:pumper_dashboard_new.integration.view')->name('admin.integration.index');
    Route::post('/admin/integration/retry-all', [IntegrationController::class, 'retryAll'])
        ->middleware('permission:pumper_dashboard_new.integration.manage')->name('admin.integration.retry-all');
    Route::post('/admin/integration/{job}/retry', [IntegrationController::class, 'retry'])
        ->whereNumber('job')->middleware('permission:pumper_dashboard_new.integration.manage')->name('admin.integration.retry');

    Route::get('/admin/settings', [SettingsController::class, 'edit'])
        ->middleware('permission:pumper_dashboard_new.settings.manage')->name('admin.settings.edit');
    Route::put('/admin/settings', [SettingsController::class, 'update'])
        ->middleware('permission:pumper_dashboard_new.settings.manage')->name('admin.settings.update');
});
