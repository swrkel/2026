<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTakingNew\Http\Controllers\ApprovalController;
use Modules\StockTakingNew\Http\Controllers\CountController;
use Modules\StockTakingNew\Http\Controllers\DashboardController;
use Modules\StockTakingNew\Http\Controllers\DocumentController;
use Modules\StockTakingNew\Http\Controllers\ImportController;
use Modules\StockTakingNew\Http\Controllers\LookupController;
use Modules\StockTakingNew\Http\Controllers\ReportController;
use Modules\StockTakingNew\Http\Controllers\ScheduleController;
use Modules\StockTakingNew\Http\Controllers\SessionController;
use Modules\StockTakingNew\Http\Controllers\SettingsController;
use Modules\StockTakingNew\Http\Controllers\ShareController;
use Modules\StockTakingNew\Http\Controllers\TemplateController;

Route::middleware('permission:stock_taking_new.access')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:stock_taking_new.dashboard.view')
        ->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:stock_taking_new.dashboard.view')
        ->name('dashboard.index');

    Route::get('/sessions', [SessionController::class, 'index'])
        ->middleware('permission:stock_taking_new.sessions.view')
        ->name('sessions.index');
    Route::get('/sessions/create', [SessionController::class, 'create'])
        ->middleware('permission:stock_taking_new.sessions.create')
        ->name('sessions.create');
    Route::post('/sessions', [SessionController::class, 'store'])
        ->middleware('permission:stock_taking_new.sessions.create')
        ->name('sessions.store');
    Route::get('/sessions/{session}', [SessionController::class, 'show'])
        ->middleware('permission:stock_taking_new.sessions.view|stock_taking_new.approvals.view|stock_taking_new.reports.view')
        ->name('sessions.show');
    Route::get('/sessions/{session}/edit', [SessionController::class, 'edit'])
        ->middleware('permission:stock_taking_new.sessions.edit')
        ->name('sessions.edit');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])
        ->middleware('permission:stock_taking_new.sessions.edit')
        ->name('sessions.update');
    Route::post('/sessions/{session}/prepare', [SessionController::class, 'prepare'])
        ->middleware('permission:stock_taking_new.sessions.prepare')
        ->name('sessions.prepare');
    Route::post('/sessions/{session}/start', [SessionController::class, 'start'])
        ->middleware('permission:stock_taking_new.sessions.start')
        ->name('sessions.start');
    Route::post('/sessions/{session}/cancel', [SessionController::class, 'cancel'])
        ->middleware('permission:stock_taking_new.sessions.edit')
        ->name('sessions.cancel');

    Route::get('/sessions/{session}/count-sheet', [CountController::class, 'sheet'])
        ->middleware('permission:stock_taking_new.counts.enter|stock_taking_new.recounts.manage')
        ->name('counts.sheet');
    Route::post('/sessions/{session}/counts', [CountController::class, 'save'])
        ->middleware('permission:stock_taking_new.counts.enter')
        ->name('counts.save');
    Route::post('/sessions/{session}/recounts', [CountController::class, 'saveRecount'])
        ->middleware('permission:stock_taking_new.recounts.manage')
        ->name('counts.recount');
    Route::post('/sessions/{session}/submit', [CountController::class, 'submit'])
        ->middleware('permission:stock_taking_new.counts.submit')
        ->name('counts.submit');

    Route::get('/approvals', [ApprovalController::class, 'index'])
        ->middleware('permission:stock_taking_new.approvals.view')
        ->name('approvals.index');
    Route::post('/sessions/{session}/approve', [ApprovalController::class, 'approve'])
        ->middleware('permission:stock_taking_new.approvals.approve')
        ->name('approvals.approve');
    Route::post('/sessions/{session}/reject', [ApprovalController::class, 'reject'])
        ->middleware('permission:stock_taking_new.approvals.reject')
        ->name('approvals.reject');
    Route::post('/sessions/{session}/post', [ApprovalController::class, 'post'])
        ->middleware('permission:stock_taking_new.reconciliation.post')
        ->name('approvals.post');

    Route::middleware('permission:stock_taking_new.reports.view')->group(function (): void {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/variance', [ReportController::class, 'variance'])->name('reports.variance');
        Route::get('/reports/progress', [ReportController::class, 'progress'])->name('reports.progress');
        Route::get('/reports/accuracy', [ReportController::class, 'accuracy'])->name('reports.accuracy');
        Route::get('/reports/audit', [ReportController::class, 'audit'])->name('reports.audit');
        Route::get('/reports/variance/export', [ReportController::class, 'exportVariance'])->name('reports.variance.export');
    });

    Route::middleware('permission:stock_taking_new.documents.print')->group(function (): void {
        Route::get('/sessions/{session}/print/{type}', [DocumentController::class, 'print'])
            ->whereIn('type', ['summary', 'count_sheet', 'variance', 'reconciliation'])
            ->name('documents.print');
        Route::get('/sessions/{session}/pdf/{type}', [DocumentController::class, 'pdf'])
            ->whereIn('type', ['summary', 'count_sheet', 'variance', 'reconciliation'])
            ->name('documents.pdf');
        Route::get('/sessions/{session}/download/{type}', [DocumentController::class, 'download'])
            ->whereIn('type', ['summary', 'count_sheet', 'variance', 'reconciliation'])
            ->name('documents.download');
    });

    Route::get('/sessions/{session}/share', [ShareController::class, 'create'])
        ->middleware('permission:stock_taking_new.documents.share')
        ->name('shares.create');
    Route::post('/sessions/{session}/share', [ShareController::class, 'store'])
        ->middleware('permission:stock_taking_new.documents.share')
        ->name('shares.store');

    Route::get('/sessions/{session}/import/template', [ImportController::class, 'template'])
        ->middleware('permission:stock_taking_new.counts.import')
        ->name('import.template');
    Route::post('/sessions/{session}/import', [ImportController::class, 'store'])
        ->middleware('permission:stock_taking_new.counts.import')
        ->name('import.store');

    Route::middleware('permission:stock_taking_new.templates.manage')->group(function (): void {
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::delete('/templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');
    });

    Route::middleware('permission:stock_taking_new.schedules.manage')->group(function (): void {
        Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
        Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('/schedules/{schedule}/toggle', [ScheduleController::class, 'toggle'])->name('schedules.toggle');
        Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    });

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('permission:stock_taking_new.settings.manage')
        ->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])
        ->middleware('permission:stock_taking_new.settings.manage')
        ->name('settings.update');

    Route::get('/lookup/locations', [LookupController::class, 'locations'])->name('lookup.locations');
    Route::get('/lookup/stores', [LookupController::class, 'stores'])->name('lookup.stores');
    Route::get('/lookup/products', [LookupController::class, 'products'])->name('lookup.products');
});
