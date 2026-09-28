<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\AuditController;
use Modules\Ran\Http\Controllers\CommunicationLogController;
use Modules\Ran\Http\Controllers\DocumentController;
use Modules\Ran\Http\Controllers\ReportController;
use Modules\Ran\Http\Controllers\SettingController;
use Modules\Ran\Http\Controllers\TemplateController;

Route::prefix('ran')->as('ran.')->middleware(['web', 'auth', 'ran.enabled'])->group(function (): void {
    Route::get('documents', [DocumentController::class, 'index'])
        ->middleware('ran.page:ran.documents.view')->name('documents.index');
    Route::get('documents/{type}/{id}', [DocumentController::class, 'compose'])
        ->middleware('ran.page:ran.documents.view')->name('documents.compose');
    Route::post('documents/{type}/{id}', [DocumentController::class, 'action'])
        ->middleware('ran.page:ran.documents.view')->name('documents.action');

    Route::get('document-templates', [TemplateController::class, 'index'])
        ->middleware('ran.page:ran.documents.manage')->name('templates.index');
    Route::get('document-templates/{template}/edit', [TemplateController::class, 'edit'])
        ->middleware('ran.page:ran.documents.manage')->name('templates.edit');
    Route::put('document-templates/{template}', [TemplateController::class, 'update'])
        ->middleware('ran.page:ran.documents.manage')->name('templates.update');

    Route::get('reports', [ReportController::class, 'index'])
        ->middleware('ran.page:ran.reports.view')->name('reports.index');
    Route::get('reports/{report}', [ReportController::class, 'show'])
        ->middleware('ran.page:ran.reports.view')->name('reports.show');
    Route::get('reports/{report}/csv', [ReportController::class, 'csv'])
        ->middleware('ran.page:ran.reports.export')->name('reports.csv');

    Route::get('communications', [CommunicationLogController::class, 'index'])
        ->middleware('ran.page:ran.documents.view')->name('communications.index');
    Route::get('audit', [AuditController::class, 'index'])
        ->middleware('ran.page:ran.audit.view')->name('audit.index');
    Route::get('settings', [SettingController::class, 'index'])
        ->middleware('ran.page:ran.settings.manage')->name('settings.index');
    Route::put('settings', [SettingController::class, 'update'])
        ->middleware('ran.page:ran.settings.manage')->name('settings.update');
});
