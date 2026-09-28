<?php
use Illuminate\Support\Facades\Route;
use Modules\EzyLaw\Http\Controllers\{
    DashboardController,ClientController,MatterController,HearingController,TaskController,TimeEntryController,
    ExpenseController,BillingController,DocumentController,ReportController,SettingsController,ChronologyController,
    PartyController,RetainerController,TrustController,CalendarController,AppointmentController,ReminderController,
    TemplateController,CommunicationController,ConflictController,WorkflowController,DeadlineController,EvidenceController,
    DocumentVersionController,AdvancedBillingController,TrustReconciliationController,WorkloadController,PortalController,
    NotificationController,LegalReportController
};
use Modules\EzyLaw\Http\Controllers\{CourtFilingController,SettlementController,ResearchController,EstimateController,AdvanceController,DocumentApprovalController,NotificationRuleController,MatterClosureController,ManagementDashboardController,TrustAuditController,PublicPortalController};

Route::group(['prefix'=>'ezylaw','as'=>'ezylaw.','middleware'=>['web','auth','ezylaw.enabled','check.route.permission']],function(){
 Route::get('/', ['uses'=>DashboardController::class.'@index','permission'=>'ezylaw_dashboard_view'])->name('dashboard');

 Route::get('/clients',['uses'=>ClientController::class.'@index','permission'=>'ezylaw_clients_view'])->name('clients.index');
 Route::get('/clients/create',['uses'=>ClientController::class.'@create','permission'=>'ezylaw_clients_create'])->name('clients.create');
 Route::post('/clients',['uses'=>ClientController::class.'@store','permission'=>'ezylaw_clients_create'])->name('clients.store');
 Route::get('/clients/{client}',['uses'=>ClientController::class.'@show','permission'=>'ezylaw_clients_view'])->name('clients.show');
 Route::get('/clients/{client}/edit',['uses'=>ClientController::class.'@edit','permission'=>'ezylaw_clients_update'])->name('clients.edit');
 Route::put('/clients/{client}',['uses'=>ClientController::class.'@update','permission'=>'ezylaw_clients_update'])->name('clients.update');
 Route::delete('/clients/{client}',['uses'=>ClientController::class.'@destroy','permission'=>'ezylaw_clients_delete'])->name('clients.destroy');

 Route::get('/matters',['uses'=>MatterController::class.'@index','permission'=>'ezylaw_matters_view'])->name('matters.index');
 Route::get('/matters/create',['uses'=>MatterController::class.'@create','permission'=>'ezylaw_matters_create'])->name('matters.create');
 Route::post('/matters',['uses'=>MatterController::class.'@store','permission'=>'ezylaw_matters_create'])->name('matters.store');
 Route::get('/matters/{matter}',['uses'=>MatterController::class.'@show','permission'=>'ezylaw_matters_view'])->name('matters.show');
 Route::get('/matters/{matter}/edit',['uses'=>MatterController::class.'@edit','permission'=>'ezylaw_matters_update'])->name('matters.edit');
 Route::put('/matters/{matter}',['uses'=>MatterController::class.'@update','permission'=>'ezylaw_matters_update'])->name('matters.update');
 Route::delete('/matters/{matter}',['uses'=>MatterController::class.'@destroy','permission'=>'ezylaw_matters_delete'])->name('matters.destroy');

 Route::get('/matters/{matter}/chronology',['uses'=>ChronologyController::class.'@index','permission'=>'ezylaw_chronology_view'])->name('chronology.index');
 Route::post('/matters/{matter}/chronology',['uses'=>ChronologyController::class.'@store','permission'=>'ezylaw_chronology_manage'])->name('chronology.store');
 Route::delete('/matters/{matter}/chronology/{entry}',['uses'=>ChronologyController::class.'@destroy','permission'=>'ezylaw_chronology_manage'])->name('chronology.destroy');
 Route::get('/matters/{matter}/parties',['uses'=>PartyController::class.'@index','permission'=>'ezylaw_parties_view'])->name('parties.index');
 Route::post('/matters/{matter}/parties',['uses'=>PartyController::class.'@store','permission'=>'ezylaw_parties_manage'])->name('parties.store');
 Route::patch('/matters/{matter}/parties/{party}',['uses'=>PartyController::class.'@update','permission'=>'ezylaw_parties_manage'])->name('parties.update');
 Route::delete('/matters/{matter}/parties/{party}',['uses'=>PartyController::class.'@destroy','permission'=>'ezylaw_parties_manage'])->name('parties.destroy');


 Route::get('/workflow',['uses'=>WorkflowController::class.'@index','permission'=>'ezylaw_workflow_view'])->name('workflow.index');
 Route::post('/workflow/templates',['uses'=>WorkflowController::class.'@template','permission'=>'ezylaw_workflow_manage'])->name('workflow.templates.store');
 Route::post('/workflow/stages',['uses'=>WorkflowController::class.'@stage','permission'=>'ezylaw_workflow_manage'])->name('workflow.stages.store');
 Route::post('/workflow/matters/{matter}/start',['uses'=>WorkflowController::class.'@start','permission'=>'ezylaw_workflow_manage'])->name('workflow.start');
 Route::patch('/workflow/history/{history}/complete',['uses'=>WorkflowController::class.'@complete','permission'=>'ezylaw_workflow_manage'])->name('workflow.complete');

 Route::get('/deadlines',['uses'=>DeadlineController::class.'@index','permission'=>'ezylaw_deadlines_view'])->name('deadlines.index');
 Route::post('/deadlines',['uses'=>DeadlineController::class.'@store','permission'=>'ezylaw_deadlines_manage'])->name('deadlines.store');
 Route::patch('/deadlines/{deadline}/complete',['uses'=>DeadlineController::class.'@complete','permission'=>'ezylaw_deadlines_manage'])->name('deadlines.complete');

 Route::get('/evidence',['uses'=>EvidenceController::class.'@index','permission'=>'ezylaw_evidence_view'])->name('evidence.index');
 Route::post('/evidence',['uses'=>EvidenceController::class.'@store','permission'=>'ezylaw_evidence_manage'])->name('evidence.store');
 Route::delete('/evidence/{evidence}',['uses'=>EvidenceController::class.'@destroy','permission'=>'ezylaw_evidence_manage'])->name('evidence.destroy');

 Route::get('/hearings',['uses'=>HearingController::class.'@index','permission'=>'ezylaw_hearings_view'])->name('hearings.index');
 Route::post('/hearings',['uses'=>HearingController::class.'@store','permission'=>'ezylaw_hearings_manage'])->name('hearings.store');
 Route::delete('/hearings/{hearing}',['uses'=>HearingController::class.'@destroy','permission'=>'ezylaw_hearings_manage'])->name('hearings.destroy');
 Route::get('/tasks',['uses'=>TaskController::class.'@index','permission'=>'ezylaw_tasks_view'])->name('tasks.index');
 Route::post('/tasks',['uses'=>TaskController::class.'@store','permission'=>'ezylaw_tasks_manage'])->name('tasks.store');
 Route::patch('/tasks/{task}',['uses'=>TaskController::class.'@update','permission'=>'ezylaw_tasks_manage'])->name('tasks.update');
 Route::get('/time',['uses'=>TimeEntryController::class.'@index','permission'=>'ezylaw_time_view'])->name('time.index');
 Route::post('/time',['uses'=>TimeEntryController::class.'@store','permission'=>'ezylaw_time_manage'])->name('time.store');

 Route::get('/retainers',['uses'=>RetainerController::class.'@index','permission'=>'ezylaw_retainers_view'])->name('retainers.index');
 Route::post('/retainers',['uses'=>RetainerController::class.'@store','permission'=>'ezylaw_retainers_manage'])->name('retainers.store');
 Route::patch('/retainers/{retainer}/close',['uses'=>RetainerController::class.'@close','permission'=>'ezylaw_retainers_manage'])->name('retainers.close');

 Route::get('/trust',['uses'=>TrustController::class.'@index','permission'=>'ezylaw_trust_view'])->name('trust.index');
 Route::post('/trust/accounts',['uses'=>TrustController::class.'@storeAccount','permission'=>'ezylaw_trust_manage'])->name('trust.accounts.store');
 Route::post('/trust/deposit',['uses'=>TrustController::class.'@deposit','permission'=>'ezylaw_trust_manage'])->name('trust.deposit');
 Route::post('/trust/withdrawal',['uses'=>TrustController::class.'@withdrawal','permission'=>'ezylaw_trust_manage'])->name('trust.withdrawal');
 Route::post('/trust/apply-invoice',['uses'=>TrustController::class.'@applyInvoice','permission'=>'ezylaw_trust_manage'])->name('trust.apply_invoice');
 Route::post('/trust/{transaction}/finance-sync',['uses'=>TrustController::class.'@sync','permission'=>'ezylaw_trust_finance'])->name('trust.sync');


 Route::get('/trust-reconciliation',['uses'=>TrustReconciliationController::class.'@index','permission'=>'ezylaw_trust_reconcile'])->name('trust.reconcile');
 Route::post('/trust-reconciliation',['uses'=>TrustReconciliationController::class.'@store','permission'=>'ezylaw_trust_reconcile'])->name('trust.reconcile.store');
 Route::patch('/trust-reconciliation/{reconciliation}/complete',['uses'=>TrustReconciliationController::class.'@complete','permission'=>'ezylaw_trust_reconcile'])->name('trust.reconcile.complete');

 Route::get('/calendar',['uses'=>CalendarController::class.'@index','permission'=>'ezylaw_calendar_view'])->name('calendar.index');
 Route::post('/calendar/appointments',['uses'=>AppointmentController::class.'@store','permission'=>'ezylaw_calendar_manage'])->name('appointments.store');
 Route::delete('/calendar/appointments/{appointment}',['uses'=>AppointmentController::class.'@destroy','permission'=>'ezylaw_calendar_manage'])->name('appointments.destroy');
 Route::post('/calendar/reminders',['uses'=>ReminderController::class.'@store','permission'=>'ezylaw_calendar_manage'])->name('reminders.store');
 Route::patch('/calendar/reminders/{reminder}/dismiss',['uses'=>ReminderController::class.'@dismiss','permission'=>'ezylaw_calendar_manage'])->name('reminders.dismiss');
 Route::delete('/calendar/reminders/{reminder}',['uses'=>ReminderController::class.'@destroy','permission'=>'ezylaw_calendar_manage'])->name('reminders.destroy');


 Route::get('/billing/rates',['uses'=>AdvancedBillingController::class.'@rates','permission'=>'ezylaw_billing_rates'])->name('billing.rates');
 Route::post('/billing/rates',['uses'=>AdvancedBillingController::class.'@storeRate','permission'=>'ezylaw_billing_rates'])->name('billing.rates.store');
 Route::delete('/billing/rates/{rate}',['uses'=>AdvancedBillingController::class.'@destroyRate','permission'=>'ezylaw_billing_rates'])->name('billing.rates.destroy');
 Route::get('/billing/matter/{matter}/preview',['uses'=>AdvancedBillingController::class.'@preview','permission'=>'ezylaw_billing_view'])->name('billing.matter.preview');
 Route::post('/billing/matter/{matter}/invoice',['uses'=>AdvancedBillingController::class.'@createInvoice','permission'=>'ezylaw_billing_manage'])->name('billing.matter.create');
 Route::post('/billing/{invoice}/adjustment',['uses'=>AdvancedBillingController::class.'@adjustment','permission'=>'ezylaw_billing_adjust'])->name('billing.adjustment');
 Route::post('/billing/adjustments/{adjustment}/finance-sync',['uses'=>AdvancedBillingController::class.'@syncAdjustment','permission'=>'ezylaw_billing_manage'])->name('billing.adjustments.sync');

 Route::get('/billing',['uses'=>BillingController::class.'@index','permission'=>'ezylaw_billing_view'])->name('billing.index');
 Route::post('/billing',['uses'=>BillingController::class.'@store','permission'=>'ezylaw_billing_manage'])->name('billing.store');
 Route::get('/billing/{invoice}',['uses'=>BillingController::class.'@show','permission'=>'ezylaw_billing_view'])->name('billing.show');
 Route::post('/billing/{invoice}/payment',['uses'=>BillingController::class.'@payment','permission'=>'ezylaw_billing_manage'])->name('billing.payment');
 Route::post('/billing/{invoice}/finance-sync',['uses'=>BillingController::class.'@sync','permission'=>'ezylaw_billing_manage'])->name('billing.sync');
 Route::get('/expenses',['uses'=>ExpenseController::class.'@index','permission'=>'ezylaw_expenses_view'])->name('expenses.index');
 Route::post('/expenses',['uses'=>ExpenseController::class.'@store','permission'=>'ezylaw_expenses_manage'])->name('expenses.store');
 Route::post('/expenses/{expense}/finance-sync',['uses'=>ExpenseController::class.'@sync','permission'=>'ezylaw_expenses_manage'])->name('expenses.sync');

 Route::get('/documents',['uses'=>DocumentController::class.'@index','permission'=>'ezylaw_documents_view'])->name('documents.index');
 Route::post('/documents',['uses'=>DocumentController::class.'@store','permission'=>'ezylaw_documents_manage'])->name('documents.store');
 Route::get('/documents/{document}/download',['uses'=>DocumentController::class.'@download','permission'=>'ezylaw_documents_view'])->name('documents.download');

 Route::post('/documents/{document}/versions',['uses'=>DocumentVersionController::class.'@store','permission'=>'ezylaw_documents_manage'])->name('documents.versions.store');
 Route::get('/document-versions/{version}/download',['uses'=>DocumentVersionController::class.'@download','permission'=>'ezylaw_documents_view'])->name('documents.versions.download');

 Route::get('/templates',['uses'=>TemplateController::class.'@index','permission'=>'ezylaw_templates_view'])->name('templates.index');
 Route::post('/templates',['uses'=>TemplateController::class.'@store','permission'=>'ezylaw_templates_manage'])->name('templates.store');
 Route::delete('/templates/{template}',['uses'=>TemplateController::class.'@destroy','permission'=>'ezylaw_templates_manage'])->name('templates.destroy');
 Route::get('/templates/{template}/render',['uses'=>TemplateController::class.'@render','permission'=>'ezylaw_templates_view'])->name('templates.render');

 Route::get('/communications',['uses'=>CommunicationController::class.'@index','permission'=>'ezylaw_communications_view'])->name('communications.index');
 Route::post('/communications',['uses'=>CommunicationController::class.'@store','permission'=>'ezylaw_communications_manage'])->name('communications.store');

 Route::get('/conflicts',['uses'=>ConflictController::class.'@index','permission'=>'ezylaw_conflicts_view'])->name('conflicts.index');
 Route::post('/conflicts/run',['uses'=>ConflictController::class.'@run','permission'=>'ezylaw_conflicts_manage'])->name('conflicts.run');
 Route::get('/conflicts/{check}',['uses'=>ConflictController::class.'@show','permission'=>'ezylaw_conflicts_view'])->name('conflicts.show');


 Route::get('/workload',['uses'=>WorkloadController::class.'@index','permission'=>'ezylaw_workload_view'])->name('workload.index');
 Route::get('/portal-admin',['uses'=>PortalController::class.'@index','permission'=>'ezylaw_portal_view'])->name('portal.index');
 Route::post('/portal-admin/access',['uses'=>PortalController::class.'@access','permission'=>'ezylaw_portal_manage'])->name('portal.access');
 Route::patch('/portal-admin/access/{access}/revoke',['uses'=>PortalController::class.'@revoke','permission'=>'ezylaw_portal_manage'])->name('portal.revoke');
 Route::post('/portal-admin/messages',['uses'=>PortalController::class.'@message','permission'=>'ezylaw_portal_manage'])->name('portal.message');

 Route::get('/notifications',['uses'=>NotificationController::class.'@index','permission'=>'ezylaw_notifications_view'])->name('notifications.index');
 Route::post('/notifications',['uses'=>NotificationController::class.'@store','permission'=>'ezylaw_notifications_manage'])->name('notifications.store');
 Route::patch('/notifications/{notification}/read',['uses'=>NotificationController::class.'@read','permission'=>'ezylaw_notifications_view'])->name('notifications.read');
 Route::post('/notifications/dispatch',['uses'=>NotificationController::class.'@dispatch','permission'=>'ezylaw_notifications_manage'])->name('notifications.dispatch');

 Route::get('/reports/legal',['uses'=>LegalReportController::class.'@index','permission'=>'ezylaw_reports_advanced'])->name('reports.legal');

 Route::get('/reports',['uses'=>ReportController::class.'@index','permission'=>'ezylaw_reports_view'])->name('reports.index');
 Route::get('/settings',['uses'=>SettingsController::class.'@index','permission'=>'ezylaw_settings_view'])->name('settings.index');
 Route::put('/settings',['uses'=>SettingsController::class.'@update','permission'=>'ezylaw_settings_manage'])->name('settings.update');
 Route::post('/settings/practice-areas',['uses'=>SettingsController::class.'@area','permission'=>'ezylaw_settings_manage'])->name('settings.areas.store');
 Route::post('/settings/courts',['uses'=>SettingsController::class.'@court','permission'=>'ezylaw_settings_manage'])->name('settings.courts.store');

 // V4 completion release: court filing, settlement, research, estimates, advances, approvals and management.
 Route::get('/court-filings',['uses'=>CourtFilingController::class.'@index','permission'=>'ezylaw_court_filings_view'])->name('court_filings.index');
 Route::post('/court-filings',['uses'=>CourtFilingController::class.'@store','permission'=>'ezylaw_court_filings_manage'])->name('court_filings.store');
 Route::patch('/court-filings/{filing}',['uses'=>CourtFilingController::class.'@update','permission'=>'ezylaw_court_filings_manage'])->name('court_filings.update');

 Route::get('/settlements',['uses'=>SettlementController::class.'@index','permission'=>'ezylaw_settlements_view'])->name('settlements.index');
 Route::post('/settlements',['uses'=>SettlementController::class.'@store','permission'=>'ezylaw_settlements_manage'])->name('settlements.store');
 Route::patch('/settlements/{settlement}/status',['uses'=>SettlementController::class.'@status','permission'=>'ezylaw_settlements_manage'])->name('settlements.status');
 Route::post('/settlements/mediation',['uses'=>SettlementController::class.'@mediation','permission'=>'ezylaw_settlements_manage'])->name('settlements.mediation');

 Route::get('/research',['uses'=>ResearchController::class.'@index','permission'=>'ezylaw_research_view'])->name('research.index');
 Route::post('/research',['uses'=>ResearchController::class.'@store','permission'=>'ezylaw_research_manage'])->name('research.store');
 Route::delete('/research/{research}',['uses'=>ResearchController::class.'@destroy','permission'=>'ezylaw_research_manage'])->name('research.destroy');

 Route::get('/estimates',['uses'=>EstimateController::class.'@index','permission'=>'ezylaw_estimates_view'])->name('estimates.index');
 Route::post('/estimates',['uses'=>EstimateController::class.'@store','permission'=>'ezylaw_estimates_manage'])->name('estimates.store');
 Route::get('/estimates/{estimate}',['uses'=>EstimateController::class.'@show','permission'=>'ezylaw_estimates_view'])->name('estimates.show');
 Route::patch('/estimates/{estimate}/status',['uses'=>EstimateController::class.'@status','permission'=>'ezylaw_estimates_manage'])->name('estimates.status');
 Route::post('/estimates/{estimate}/convert',['uses'=>EstimateController::class.'@convert','permission'=>'ezylaw_estimates_convert'])->name('estimates.convert');

 Route::get('/advances',['uses'=>AdvanceController::class.'@index','permission'=>'ezylaw_advances_view'])->name('advances.index');
 Route::post('/advances',['uses'=>AdvanceController::class.'@deposit','permission'=>'ezylaw_advances_manage'])->name('advances.deposit');
 Route::post('/advances/{advance}/allocate',['uses'=>AdvanceController::class.'@allocate','permission'=>'ezylaw_advances_allocate'])->name('advances.allocate');
 Route::post('/advances/{advance}/finance-sync',['uses'=>AdvanceController::class.'@syncAdvance','permission'=>'ezylaw_advances_finance'])->name('advances.sync');
 Route::post('/advance-allocations/{allocation}/finance-sync',['uses'=>AdvanceController::class.'@syncAllocation','permission'=>'ezylaw_advances_finance'])->name('advances.allocations.sync');

 Route::get('/document-approvals',['uses'=>DocumentApprovalController::class.'@index','permission'=>'ezylaw_document_approvals_view'])->name('approvals.index');
 Route::post('/document-approvals',['uses'=>DocumentApprovalController::class.'@requestApproval','permission'=>'ezylaw_document_approvals_manage'])->name('approvals.request');
 Route::patch('/document-approvals/{approval}/respond',['uses'=>DocumentApprovalController::class.'@respond','permission'=>'ezylaw_document_approvals_respond'])->name('approvals.respond');
 Route::post('/document-approvals/esign',['uses'=>DocumentApprovalController::class.'@requestEsign','permission'=>'ezylaw_esign_manage'])->name('approvals.esign');

 Route::get('/notification-rules',['uses'=>NotificationRuleController::class.'@index','permission'=>'ezylaw_notification_rules_view'])->name('notification_rules.index');
 Route::post('/notification-rules',['uses'=>NotificationRuleController::class.'@store','permission'=>'ezylaw_notification_rules_manage'])->name('notification_rules.store');
 Route::patch('/notification-rules/{rule}/toggle',['uses'=>NotificationRuleController::class.'@toggle','permission'=>'ezylaw_notification_rules_manage'])->name('notification_rules.toggle');
 Route::delete('/notification-rules/{rule}',['uses'=>NotificationRuleController::class.'@destroy','permission'=>'ezylaw_notification_rules_manage'])->name('notification_rules.destroy');
 Route::post('/notification-rules/run',['uses'=>NotificationRuleController::class.'@run','permission'=>'ezylaw_notification_rules_manage'])->name('notification_rules.run');

 Route::get('/matter-closures',['uses'=>MatterClosureController::class.'@index','permission'=>'ezylaw_matter_closure_view'])->name('closures.index');
 Route::post('/matter-closures/{matter}',['uses'=>MatterClosureController::class.'@close','permission'=>'ezylaw_matter_closure_manage'])->name('closures.close');
 Route::patch('/matter-closures/{closure}/reopen',['uses'=>MatterClosureController::class.'@reopen','permission'=>'ezylaw_matter_closure_manage'])->name('closures.reopen');

 Route::get('/management-dashboard',['uses'=>ManagementDashboardController::class.'@index','permission'=>'ezylaw_management_dashboard_view'])->name('management.index');
 Route::post('/management-dashboard/targets',['uses'=>ManagementDashboardController::class.'@target','permission'=>'ezylaw_management_dashboard_manage'])->name('management.targets.store');
 Route::get('/trust-audit',['uses'=>TrustAuditController::class.'@index','permission'=>'ezylaw_trust_audit_view'])->name('trust.audit');

});


if ((bool) config('ezylaw.portal_public_routes', false)) {
    Route::group(['prefix'=>'ezylaw-portal','as'=>'ezylaw.portal.public.','middleware'=>['web','throttle:30,1']], function () {
        Route::get('/{token}', PublicPortalController::class.'@show')->name('show');
        Route::post('/{token}/messages', PublicPortalController::class.'@message')->name('message');
        Route::post('/{token}/esign/{esign}', PublicPortalController::class.'@esign')->name('esign');
    });
}
