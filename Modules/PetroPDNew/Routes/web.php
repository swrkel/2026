<?php

use Illuminate\Support\Facades\Route;
use Modules\PetroPDNew\Http\Controllers\AuditController;
use Modules\PetroPDNew\Http\Controllers\DashboardController;
use Modules\PetroPDNew\Http\Controllers\DailyPumpStatusController;
use Modules\PetroPDNew\Http\Controllers\DayEndController;
use Modules\PetroPDNew\Http\Controllers\DocumentController;
use Modules\PetroPDNew\Http\Controllers\IntegrationController;
use Modules\PetroPDNew\Http\Controllers\NotificationTemplateController;
use Modules\PetroPDNew\Http\Controllers\OperatorMappingController;
use Modules\PetroPDNew\Http\Controllers\OperatorActionController;
use Modules\PetroPDNew\Http\Controllers\OperatorTabActionController;
use Modules\PetroPDNew\Http\Controllers\PrintController;
use Modules\PetroPDNew\Http\Controllers\ReconciliationController;
use Modules\PetroPDNew\Http\Controllers\ReportController;
use Modules\PetroPDNew\Http\Controllers\SettingsController;
use Modules\PetroPDNew\Http\Controllers\SettlementAdjustmentController;
use Modules\PetroPDNew\Http\Controllers\SettlementController;
use Modules\PetroPDNew\Http\Controllers\SettlementPaymentController;
use Modules\PetroPDNew\Http\Controllers\SettlementWorkflowController;
use Modules\PetroPDNew\Http\Controllers\SourceShiftController;

Route::get('/', [DashboardController::class, 'index'])
    ->middleware('permission:petro_pd_new.dashboard.view')->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('permission:petro_pd_new.dashboard.view')->name('dashboard.index');

Route::get('/source-shifts', [SourceShiftController::class, 'index'])
    ->middleware('permission:petro_pd_new.sources.view')->name('sources.index');
Route::get('/source-shifts/{shift}', [SourceShiftController::class, 'show'])
    ->whereNumber('shift')->middleware('permission:petro_pd_new.sources.view')->name('sources.show');
Route::post('/source-shifts/{shift}/import', [SourceShiftController::class, 'import'])
    ->whereNumber('shift')->middleware('permission:petro_pd_new.sources.import')->name('sources.import');
Route::post('/source-shifts/{shift}/create-settlement', [SourceShiftController::class, 'createSettlement'])
    ->whereNumber('shift')->middleware('permission:petro_pd_new.settlements.create')->name('sources.create-settlement');
Route::post('/source-imports/{source}/verify', [SourceShiftController::class, 'verify'])
    ->whereNumber('source')->middleware('permission:petro_pd_new.sources.refresh')->name('sources.verify');

Route::get('/settlements', [SettlementController::class, 'index'])
    ->middleware('permission:petro_pd_new.settlements.view')->name('settlements.index');
Route::get('/settlements/create', [SettlementController::class, 'create'])
    ->middleware('permission:petro_pd_new.settlements.create')->name('settlements.create');
Route::post('/settlements', [SettlementController::class, 'store'])
    ->middleware('permission:petro_pd_new.settlements.create')->name('settlements.store');
Route::get('/settlements/{settlement}', [SettlementController::class, 'show'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.view')->name('settlements.show');
Route::get('/settlements/{settlement}/edit', [SettlementController::class, 'edit'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.edit')->name('settlements.edit');
Route::put('/settlements/{settlement}', [SettlementController::class, 'update'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.edit')->name('settlements.update');
Route::post('/settlements/{settlement}/refresh-source', [SettlementController::class, 'refresh'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.sources.refresh')->name('settlements.refresh');
Route::post('/settlements/{settlement}/cancel', [SettlementController::class, 'cancel'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.cancel')->name('settlements.cancel');

Route::post('/settlements/{settlement}/payments', [SettlementPaymentController::class, 'store'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.payments.manage')->name('payments.store');
Route::put('/payments/{payment}', [SettlementPaymentController::class, 'update'])
    ->whereNumber('payment')->middleware('permission:petro_pd_new.payments.manage')->name('payments.update');
Route::delete('/payments/{payment}', [SettlementPaymentController::class, 'void'])
    ->whereNumber('payment')->middleware('permission:petro_pd_new.payments.manage')->name('payments.void');
Route::get('/payments/check-reference', [SettlementPaymentController::class, 'checkReference'])
    ->middleware('permission:petro_pd_new.payments.manage')->name('payments.check-reference');

Route::post('/settlements/{settlement}/adjustments', [SettlementAdjustmentController::class, 'store'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.adjustments.request')->name('adjustments.store');
Route::post('/adjustments/{adjustment}/approve', [SettlementAdjustmentController::class, 'approve'])
    ->whereNumber('adjustment')->middleware('permission:petro_pd_new.adjustments.approve')->name('adjustments.approve');
Route::post('/adjustments/{adjustment}/reject', [SettlementAdjustmentController::class, 'reject'])
    ->whereNumber('adjustment')->middleware('permission:petro_pd_new.adjustments.approve')->name('adjustments.reject');

Route::post('/settlements/{settlement}/submit', [SettlementWorkflowController::class, 'submit'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.workflow.review')->name('workflow.submit');
Route::post('/settlements/{settlement}/return', [SettlementWorkflowController::class, 'returnToDraft'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.workflow.review')->name('workflow.return');
Route::post('/settlements/{settlement}/approve', [SettlementWorkflowController::class, 'approve'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.workflow.approve')->name('workflow.approve');
Route::post('/settlements/{settlement}/finalize', [SettlementWorkflowController::class, 'finalize'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.workflow.finalize')->name('workflow.finalize');
Route::post('/settlements/{settlement}/reopen', [SettlementWorkflowController::class, 'reopen'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.reopen')->name('workflow.reopen');

Route::post('/settlements/{settlement}/reconcile', [ReconciliationController::class, 'evaluate'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.reconciliation.manage')->name('reconciliation.evaluate');
Route::post('/reconciliation-issues/{issue}/resolve', [ReconciliationController::class, 'resolve'])
    ->whereNumber('issue')->middleware('permission:petro_pd_new.reconciliation.manage')->name('reconciliation.resolve');

Route::get('/day-ends', [DayEndController::class, 'index'])
    ->middleware('permission:petro_pd_new.day_end.view')->name('day-ends.index');
Route::get('/day-ends/create', [DayEndController::class, 'create'])
    ->middleware('permission:petro_pd_new.day_end.manage')->name('day-ends.create');
Route::post('/day-ends', [DayEndController::class, 'store'])
    ->middleware('permission:petro_pd_new.day_end.manage')->name('day-ends.store');
Route::get('/day-ends/{dayEnd}', [DayEndController::class, 'show'])
    ->whereNumber('dayEnd')->middleware('permission:petro_pd_new.day_end.view')->name('day-ends.show');
Route::post('/day-ends/{dayEnd}/refresh', [DayEndController::class, 'refresh'])
    ->whereNumber('dayEnd')->middleware('permission:petro_pd_new.day_end.manage')->name('day-ends.refresh');
Route::post('/day-ends/{dayEnd}/finalize', [DayEndController::class, 'finalize'])
    ->whereNumber('dayEnd')->middleware('permission:petro_pd_new.day_end.finalize')->name('day-ends.finalize');

Route::get('/operators', [OperatorMappingController::class, 'index'])
    ->middleware('permission:petro_pd_new.operators.view')->name('operators.index');
Route::get('/operators/tab', [OperatorMappingController::class, 'tab'])
    ->middleware('permission:petro_pd_new.operators.view')->name('operators.tab');
Route::post('/operators/sync', [OperatorMappingController::class, 'sync'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.sync');
Route::put('/operators/{operator}', [OperatorMappingController::class, 'update'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.update');
Route::get('/operators/daily-pump-status/assign', [DailyPumpStatusController::class, 'assignModal'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.assign');
Route::post('/operators/daily-pump-status/assign', [DailyPumpStatusController::class, 'store'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.store');
Route::get('/operators/daily-pump-status/{assignment}/edit', [DailyPumpStatusController::class, 'editModal'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.edit');
Route::put('/operators/daily-pump-status/{assignment}', [DailyPumpStatusController::class, 'update'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.update');
Route::get('/operators/daily-pump-status/{assignment}/cancel', [DailyPumpStatusController::class, 'cancelModal'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.cancel');
Route::delete('/operators/daily-pump-status/{assignment}', [DailyPumpStatusController::class, 'destroy'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.daily-status.destroy');

Route::get('/operators/{operator}/action/{section}', [OperatorActionController::class, 'modal'])
    ->whereNumber('operator')->where('section', 'view|edit|commission|recovery|contact|ledger|commissions|documents')
    ->middleware('permission:petro_pd_new.operators.view')->name('operators.action');
Route::put('/operators/{operator}/profile', [OperatorActionController::class, 'update'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.profile.update');
Route::post('/operators/{operator}/toggle', [OperatorActionController::class, 'toggle'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.toggle');
Route::post('/operators/{operator}/commission', [OperatorActionController::class, 'commission'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.commission');
Route::post('/operators/{operator}/recovery', [OperatorActionController::class, 'recovery'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.recovery');
Route::post('/operators/{operator}/notes', [OperatorActionController::class, 'note'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.notes.store');
Route::post('/operators/{operator}/documents', [OperatorActionController::class, 'document'])
    ->whereNumber('operator')->middleware('permission:petro_pd_new.operators.manage')->name('operators.documents.store');
Route::get('/operators/{operator}/documents/{document}/download', [OperatorActionController::class, 'download'])
    ->whereNumber('operator')->whereNumber('document')->middleware('permission:petro_pd_new.operators.view')->name('operators.documents.download');

Route::get('/operators/workspace/{type}/{id}/{action}', [OperatorTabActionController::class, 'modal'])
    ->whereNumber('id')->where('type', 'day-entry|payment|shift|assignment|unload|variance|meter-payment|day-end')
    ->where('action', 'view|create|edit|void|current|close|print')
    ->middleware('permission:petro_pd_new.operators.view')->name('operators.workspace.modal');
Route::post('/operators/workspace/day-entries', [OperatorTabActionController::class, 'createDayEntry'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.day-entries.store');
Route::put('/operators/workspace/day-entries/{entry}', [OperatorTabActionController::class, 'updateDayEntry'])
    ->whereNumber('entry')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.day-entries.update');
Route::delete('/operators/workspace/day-entries/{entry}', [OperatorTabActionController::class, 'voidDayEntry'])
    ->whereNumber('entry')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.day-entries.void');
Route::post('/operators/workspace/payments', [OperatorTabActionController::class, 'createPayment'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.payments.store');
Route::put('/operators/workspace/payments/{payment}', [OperatorTabActionController::class, 'updatePayment'])
    ->whereNumber('payment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.payments.update');
Route::delete('/operators/workspace/payments/{payment}', [OperatorTabActionController::class, 'voidPayment'])
    ->whereNumber('payment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.payments.void');
Route::post('/operators/workspace/assignments/{assignment}/current-meter', [OperatorTabActionController::class, 'currentMeter'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.assignments.current-meter');
Route::post('/operators/workspace/assignments/{assignment}/close', [OperatorTabActionController::class, 'closePump'])
    ->whereNumber('assignment')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.assignments.close');
Route::post('/operators/workspace/shifts/{shift}/close', [OperatorTabActionController::class, 'closeShift'])
    ->whereNumber('shift')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.shifts.close');
Route::post('/operators/workspace/unloads', [OperatorTabActionController::class, 'createUnload'])
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.unloads.store');
Route::put('/operators/workspace/unloads/{unload}', [OperatorTabActionController::class, 'updateUnload'])
    ->whereNumber('unload')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.unloads.update');
Route::delete('/operators/workspace/unloads/{unload}', [OperatorTabActionController::class, 'voidUnload'])
    ->whereNumber('unload')->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.unloads.void');
Route::delete('/operators/workspace/variance/{kind}/{record}', [OperatorTabActionController::class, 'voidVariance'])
    ->where('kind', 'shortage|commission')->whereNumber('record')
    ->middleware('permission:petro_pd_new.operators.manage')->name('operators.workspace.variance.void');

Route::get('/reports/{report?}', [ReportController::class, 'index'])
    ->middleware('permission:petro_pd_new.reports.view')->name('reports.index');
Route::get('/reports/{report}/print', [ReportController::class, 'print'])
    ->middleware('permission:petro_pd_new.reports.print')->name('reports.print');
Route::get('/reports/{report}/export/{format?}', [ReportController::class, 'export'])
    ->where('format', 'csv|excel|pdf')
    ->middleware('permission:petro_pd_new.reports.export')->name('reports.export');

Route::get('/integration', [IntegrationController::class, 'index'])
    ->middleware('permission:petro_pd_new.integration.view')->name('integration.index');
Route::post('/integration/retry-all', [IntegrationController::class, 'retryAll'])
    ->middleware('permission:petro_pd_new.integration.manage')->name('integration.retry-all');
Route::post('/integration/verify-sources', [IntegrationController::class, 'verifySources'])
    ->middleware('permission:petro_pd_new.integration.manage')->name('integration.verify-sources');
Route::post('/integration/{job}/retry', [IntegrationController::class, 'retry'])
    ->whereNumber('job')->middleware('permission:petro_pd_new.integration.manage')->name('integration.retry');

Route::get('/notifications', [NotificationTemplateController::class, 'index'])
    ->middleware('permission:petro_pd_new.notifications.manage')->name('notifications.index');
Route::post('/notifications', [NotificationTemplateController::class, 'store'])
    ->middleware('permission:petro_pd_new.notifications.manage')->name('notifications.store');
Route::put('/notifications/{template}', [NotificationTemplateController::class, 'update'])
    ->whereNumber('template')->middleware('permission:petro_pd_new.notifications.manage')->name('notifications.update');
Route::delete('/notifications/{template}', [NotificationTemplateController::class, 'destroy'])
    ->whereNumber('template')->middleware('permission:petro_pd_new.notifications.manage')->name('notifications.destroy');

Route::get('/audit', [AuditController::class, 'index'])
    ->middleware('permission:petro_pd_new.audit.view')->name('audit.index');
Route::get('/user-activity', [AuditController::class, 'index'])
    ->middleware('permission:petro_pd_new.audit.view')->name('audit.user-activity');
Route::get('/settings', [SettingsController::class, 'edit'])
    ->middleware('permission:petro_pd_new.settings.manage')->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])
    ->middleware('permission:petro_pd_new.settings.manage')->name('settings.update');

Route::get('/settlements/{settlement}/print', [PrintController::class, 'settlement'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.print')->name('settlements.print');
Route::get('/day-ends/{dayEnd}/print', [PrintController::class, 'dayEnd'])
    ->whereNumber('dayEnd')->middleware('permission:petro_pd_new.print')->name('day-ends.print');

Route::post('/settlements/{settlement}/documents', [DocumentController::class, 'store'])
    ->whereNumber('settlement')->middleware('permission:petro_pd_new.settlements.edit')->name('documents.store');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])
    ->whereNumber('document')->middleware('permission:petro_pd_new.settlements.view')->name('documents.download');
Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])
    ->whereNumber('document')->middleware('permission:petro_pd_new.settlements.edit')->name('documents.destroy');
