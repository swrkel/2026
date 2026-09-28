<?php

use Illuminate\Support\Facades\Route;
use Modules\AutoService\Http\Controllers\DashboardController;
use Modules\AutoService\Http\Controllers\CommandCentreController;
use Modules\AutoService\Http\Controllers\VehicleController;
use Modules\AutoService\Http\Controllers\JobController;
use Modules\AutoService\Http\Controllers\TimelineController;
use Modules\AutoService\Http\Controllers\ReminderController;
use Modules\AutoService\Http\Controllers\ReportController;
use Modules\AutoService\Http\Controllers\SettingsController;
use Modules\AutoService\Http\Controllers\AppointmentController;
use Modules\AutoService\Http\Controllers\EstimateController;
use Modules\AutoService\Http\Controllers\InspectionController;
use Modules\AutoService\Http\Controllers\CustomerPortalController;
use Modules\AutoService\Http\Controllers\CustomerExperienceController;
use Modules\AutoService\Http\Controllers\ServiceAdvisorWorkspaceController;
use Modules\AutoService\Http\Controllers\ReceptionController;
use Modules\AutoService\Http\Controllers\MechanicController;
use Modules\AutoService\Http\Controllers\ServicePackageController;
use Modules\AutoService\Http\Controllers\WorkshopController;
use Modules\AutoService\Http\Controllers\PaymentController;
use Modules\AutoService\Http\Controllers\InvoiceController;
use Modules\AutoService\Http\Controllers\DocumentController;
use Modules\AutoService\Http\Controllers\ApprovalController;
use Modules\AutoService\Http\Controllers\NotificationController;
use Modules\AutoService\Http\Controllers\CalendarController;
use Modules\AutoService\Http\Controllers\WhiteboardController;
use Modules\AutoService\Http\Controllers\MechanicDashboardController;
use Modules\AutoService\Http\Controllers\BayController;
use Modules\AutoService\Http\Controllers\CommunicationController;
use Modules\AutoService\Http\Controllers\ReleaseAuditController;
use Modules\AutoService\Http\Controllers\QualityControlController;
use Modules\AutoService\Http\Controllers\DeliveryController;
use Modules\AutoService\Http\Controllers\CompletionController;
use Modules\AutoService\Http\Controllers\PartsLabourController;
use Modules\AutoService\Http\Controllers\ServiceFlowController;
use Modules\AutoService\Http\Controllers\BillingDeliveryController;
use Modules\AutoService\Http\Controllers\CustomerCareController;
use Modules\AutoService\Http\Controllers\MaintenancePlannerController;
use Modules\AutoService\Http\Controllers\ManagementKpiController;
use Modules\AutoService\Http\Controllers\InventoryControlController;
use Modules\AutoService\Http\Controllers\AdvancedVehicleHistoryController;
use Modules\AutoService\Http\Controllers\WorkshopPlanningController;
use Modules\AutoService\Http\Controllers\BusinessIntelligenceController;
use Modules\AutoService\Http\Controllers\DealerEnterpriseController;
use Modules\AutoService\Http\Controllers\ProductionAuditController;
use Modules\AutoService\Http\Controllers\StabilizationController;
use Modules\AutoService\Http\Controllers\DeploymentDiagnosticsController;
use Modules\AutoService\Http\Controllers\EnterpriseIntegrationController;
use Modules\AutoService\Http\Controllers\UiStandardizationController;
use Modules\AutoService\Http\Controllers\Central\CentralVehiclePortalController;



Route::prefix('auto-service/central-vehicle')->as('autoservice.central_vehicle.')->middleware(['web'])->group(function () {
    Route::get('register', [CentralVehiclePortalController::class, 'register'])->name('register');
    Route::post('register', [CentralVehiclePortalController::class, 'store'])->name('store');
    Route::get('login', [CentralVehiclePortalController::class, 'login'])->name('login');
    Route::post('request-otp', [CentralVehiclePortalController::class, 'requestOtp'])->name('request_otp');
    Route::get('verify/{vehicle}', [CentralVehiclePortalController::class, 'verifyForm'])->name('verify.form');
    Route::post('verify/{vehicle}', [CentralVehiclePortalController::class, 'verify'])->name('verify');
    Route::get('dashboard', [CentralVehiclePortalController::class, 'dashboard'])->name('dashboard');
    Route::post('transfer/request/{vehicle}', [CentralVehiclePortalController::class, 'requestOwnershipTransfer'])->name('transfer.request');
    Route::get('transfer/{transfer}/verify-new-owner', [CentralVehiclePortalController::class, 'verifyNewOwnerForm'])->name('transfer.verify_new_owner.form');
    Route::post('transfer/{transfer}/verify-new-owner', [CentralVehiclePortalController::class, 'verifyNewOwner'])->name('transfer.verify_new_owner');
    Route::post('logout', [CentralVehiclePortalController::class, 'logout'])->name('logout');
});

Route::prefix('auto-service')->as('autoservice.')->middleware(['web'])->group(function () {
    Route::get('customer-login', [CustomerPortalController::class, 'customerLogin'])->name('customer_portal.login');
    Route::get('customer-portal/invoices/{invoice}', [CustomerPortalController::class, 'invoiceDetail'])->name('customer_portal.invoices.show');
    Route::post('customer-portal/feedback', [CustomerPortalController::class, 'submitFeedback'])->name('customer_portal.feedback.store');
    Route::post('customer-portal/appointment-request', [CustomerPortalController::class, 'requestAppointment'])->name('customer_portal.appointment_request.store');
    Route::post('customer-portal/approvals/{approval}/respond', [CustomerPortalController::class, 'respondApproval'])->name('customer_portal.approvals.respond');
    Route::post('customer-portal/documents', [CustomerPortalController::class, 'uploadDocument'])->name('customer_portal.documents.store');
    Route::get('customer-portal/documents/{document}', [CustomerPortalController::class, 'viewDocument'])->name('customer_portal.documents.show');
    Route::post('customer-portal/alerts/{alert}/acknowledge', [CustomerPortalController::class, 'acknowledgeAlert'])->name('customer_portal.alerts.acknowledge');
    Route::get('customer-portal/service-summary/print', [CustomerPortalController::class, 'downloadServiceSummary'])->name('customer_portal.service_summary.print');
    Route::get('customer-portal/parts-history/export', [CustomerPortalController::class, 'exportPartsHistoryCsv'])->name('customer_portal.parts_history.export');
    Route::get('customer-portal/payment-history', [CustomerPortalController::class, 'paymentHistory'])->name('customer_portal.payment_history');
    Route::get('customer-experience/live-progress', [CustomerExperienceController::class, 'liveProgress'])->name('customer_experience.live_progress');
    Route::post('customer-experience/callback', [CustomerExperienceController::class, 'requestCallback'])->name('customer_experience.callback');
    Route::get('customer-experience/job-card/{job}', [CustomerExperienceController::class, 'jobCard'])->name('customer_experience.job_card');
    Route::get('customer-experience/inspection-report/{job}', [CustomerExperienceController::class, 'inspectionReport'])->name('customer_experience.inspection_report');
    Route::get('customer-experience/warranty-certificate/{job}', [CustomerExperienceController::class, 'warrantyCertificate'])->name('customer_experience.warranty_certificate');
});

Route::prefix('auto-service')->as('autoservice.')->middleware(['web','auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('command-centre', [CommandCentreController::class, 'index'])->name('command_centre.index');
    Route::get('command-centre/live', [CommandCentreController::class, 'live'])->name('command_centre.live');
    Route::get('command-center', [CommandCentreController::class, 'index'])->name('command_center.index');
    Route::get('workspace', [ServiceAdvisorWorkspaceController::class, 'index'])->name('workspace.index');
    Route::resource('receptions', ReceptionController::class)->only(['index','create','store']);
    Route::resource('vehicles', VehicleController::class)->except(['destroy']);
    Route::get('vehicle-search', [VehicleController::class, 'search'])->name('vehicles.search');
    Route::get('central-vehicle/workshop-history', [CentralVehiclePortalController::class, 'workshopHistory'])->name('central_vehicle.workshop_history');
    Route::resource('jobs', JobController::class)->except(['destroy']);

    Route::resource('mechanics', MechanicController::class)->except(['show','destroy']);
    Route::resource('packages', ServicePackageController::class)->except(['show','destroy']);
    Route::get('workshop', [WorkshopController::class, 'index'])->name('workshop.index');
    Route::get('parts-labour', [PartsLabourController::class, 'index'])->name('parts_labour.index');
    Route::get('parts-labour/{job}/edit', [PartsLabourController::class, 'edit'])->name('parts_labour.edit');
    Route::get('service-flow', [ServiceFlowController::class, 'index'])->name('service_flow.index');
    Route::get('billing-delivery', [BillingDeliveryController::class, 'index'])->name('billing_delivery.index');
    Route::post('billing-delivery/jobs/{job}/generate-invoice', [BillingDeliveryController::class, 'generateInvoice'])->name('billing_delivery.generate_invoice');
    Route::post('billing-delivery/invoices/{invoice}/mark-paid', [BillingDeliveryController::class, 'markPaid'])->name('billing_delivery.mark_paid');
    Route::post('billing-delivery/jobs/{job}/release', [BillingDeliveryController::class, 'release'])->name('billing_delivery.release');

    Route::post('service-flow/assign', [ServiceFlowController::class, 'assign'])->name('service_flow.assign');
    Route::post('service-flow/inspection', [ServiceFlowController::class, 'inspectionCheckpoint'])->name('service_flow.inspection');
    Route::post('service-flow/jobs/{job}/ready-for-qc', [ServiceFlowController::class, 'readyForQc'])->name('service_flow.ready_for_qc');
    Route::post('service-flow/assignments/{assignment}/status', [ServiceFlowController::class, 'mechanicStatus'])->name('service_flow.mechanic_status');
    Route::post('parts-labour/{job}/parts', [PartsLabourController::class, 'saveParts'])->name('parts_labour.parts');
    Route::post('parts-labour/{job}/labour', [PartsLabourController::class, 'saveLabour'])->name('parts_labour.labour');
    Route::get('whiteboard', [WhiteboardController::class, 'index'])->name('whiteboard.index');
    Route::get('quality-control', [QualityControlController::class, 'index'])->name('quality_control.index');
    Route::post('quality-control', [QualityControlController::class, 'store'])->name('quality_control.store');
    Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('mechanic-dashboard', [MechanicDashboardController::class, 'index'])->name('mechanic_dashboard.index');
    Route::get('bays', [BayController::class, 'index'])->name('bays.index');
    Route::post('bays', [BayController::class, 'store'])->name('bays.store');
    Route::post('bays/{id}/release', [BayController::class, 'release'])->name('bays.release');
    Route::get('communications', [CommunicationController::class, 'index'])->name('communications.index');
    Route::post('communications', [CommunicationController::class, 'store'])->name('communications.store');
    Route::post('communications/{id}/mark-sent', [CommunicationController::class, 'markSent'])->name('communications.mark_sent');
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('workshop/jobs/{job}', [WorkshopController::class, 'job'])->name('workshop.job');
    Route::post('workshop/jobs/{job}/status', [WorkshopController::class, 'status'])->name('workshop.status');
    Route::post('workshop/jobs/{job}/mechanics', [WorkshopController::class, 'mechanics'])->name('workshop.mechanics');
    Route::post('workshop/jobs/{job}/parts', [WorkshopController::class, 'parts'])->name('workshop.parts');

    Route::resource('appointments', AppointmentController::class)->except(['show','destroy']);
    Route::resource('estimates', EstimateController::class)->except(['destroy']);
    Route::post('estimates/{estimate}/approve', [EstimateController::class, 'approve'])->name('estimates.approve');
    Route::post('estimates/{estimate}/convert-to-job', [EstimateController::class, 'convertToJob'])->name('estimates.convert_to_job');
    Route::resource('inspections', InspectionController::class)->except(['show','destroy']);

    Route::resource('invoices', InvoiceController::class)->except(['destroy']);
    Route::resource('documents', DocumentController::class)->only(['index','create','store']);
    Route::resource('approvals', ApprovalController::class)->only(['index','create','store']);
    Route::post('approvals/{id}/respond', [ApprovalController::class, 'respond'])->name('approvals.respond');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{id}/mark-sent', [NotificationController::class, 'markSent'])->name('notifications.mark_sent');
    Route::resource('payments', PaymentController::class)->except(['show','destroy']);
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::post('jobs/{job}/generate-invoice', [InvoiceController::class, 'fromJob'])->name('jobs.generate_invoice');
    Route::post('jobs/{job}/start', [JobController::class, 'start'])->name('jobs.start');
    Route::post('jobs/{job}/hold', [JobController::class, 'hold'])->name('jobs.hold');
    Route::post('jobs/{job}/complete', [JobController::class, 'complete'])->name('jobs.complete');
    Route::post('jobs/{job}/deliver', [JobController::class, 'deliver'])->name('jobs.deliver');
    Route::get('customer-portal/lookup', [CustomerPortalController::class, 'lookup'])->name('customer_portal.lookup');
    Route::get('customer-portal/status', [CustomerPortalController::class, 'lookup'])->name('customer_portal.status');
    Route::get('jobs/{job}/print', [JobController::class, 'print'])->name('jobs.print');
    Route::get('vehicles/{vehicle}/timeline', [TimelineController::class, 'show'])->name('vehicles.timeline');
    Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
    Route::post('reminders/{id}/mark-sent', [ReminderController::class, 'markSent'])->name('reminders.mark_sent');
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/service-due', [ReportController::class, 'serviceDue'])->name('reports.service_due');
    Route::get('reports/profitability', [ReportController::class, 'profitability'])->name('reports.profitability');
    Route::get('reports/daily-summary', [ReportController::class, 'dailySummary'])->name('reports.daily_summary');
    Route::get('reports/mechanic-performance', [ReportController::class, 'mechanicPerformance'])->name('reports.mechanic_performance');
    Route::get('reports/invoice-aging', [ReportController::class, 'invoiceAging'])->name('reports.invoice_aging');
    Route::get('reports/accounting-preview/{invoice}', [ReportController::class, 'accountingPreview'])->name('reports.accounting_preview');
    Route::get('release-audit', [ReleaseAuditController::class, 'index'])->name('release.audit');
    Route::get('customer-care', [CustomerCareController::class, 'index'])->name('customer_care.index');
    Route::get('maintenance-planner', [MaintenancePlannerController::class, 'index'])->name('maintenance_planner.index');
    Route::post('maintenance-planner', [MaintenancePlannerController::class, 'store'])->name('maintenance_planner.store');
    Route::post('maintenance-planner/{plan}/status', [MaintenancePlannerController::class, 'updateStatus'])->name('maintenance_planner.status');
    Route::get('maintenance-planner/vehicle-health', [MaintenancePlannerController::class, 'vehicleHealth'])->name('maintenance_planner.vehicle_health');
    Route::post('customer-care/reminders', [CustomerCareController::class, 'generateReminder'])->name('customer_care.reminders.store');
    Route::post('customer-care/warranty', [CustomerCareController::class, 'createWarrantyClaim'])->name('customer_care.warranty.store');
    Route::post('customer-care/warranty/{claim}/status', [CustomerCareController::class, 'updateWarrantyStatus'])->name('customer_care.warranty.status');
    Route::post('customer-care/feedback/{feedback}/close', [CustomerCareController::class, 'closeFeedback'])->name('customer_care.feedback.close');
    Route::get('completion-check', [CompletionController::class, 'index'])->name('completion.index');
    Route::get('production-audit', [ProductionAuditController::class, 'index'])->name('production_audit.index');
    Route::get('stabilization-centre', [StabilizationController::class, 'index'])->name('stabilization.index');
    Route::get('deployment-diagnostics', [DeploymentDiagnosticsController::class, 'index'])->name('deployment_diagnostics.index');
    Route::get('enterprise-integration', [EnterpriseIntegrationController::class, 'index'])->name('enterprise_integration.index');
    Route::post('enterprise-integration/log-check', [EnterpriseIntegrationController::class, 'logCheck'])->name('enterprise_integration.log_check');
    Route::get('ui-standardization-performance', [UiStandardizationController::class, 'index'])->name('ui_standardization.index');
    Route::post('ui-standardization-performance/log-check', [UiStandardizationController::class, 'logCheck'])->name('ui_standardization.log_check');
    Route::get('ui-standardization-performance', [UiStandardizationController::class, 'index'])->name('ui_standardization.index');
    Route::post('ui-standardization-performance/log-check', [UiStandardizationController::class, 'logCheck'])->name('ui_standardization.log_check');
    Route::post('stabilization-centre/issues', [StabilizationController::class, 'storeIssue'])->name('stabilization.issues.store');
    Route::post('stabilization-centre/issues/{id}', [StabilizationController::class, 'updateIssue'])->name('stabilization.issues.update');

    Route::get('management-kpi', [ManagementKpiController::class, 'index'])->name('management_kpi.index');
    Route::post('management-kpi/repeat-repairs', [ManagementKpiController::class, 'repeatRepairStore'])->name('management_kpi.repeat_repairs.store');

    Route::get('management-kpi', [ManagementKpiController::class, 'index'])->name('management_kpi.index');
    Route::post('management-kpi/repeat-repairs', [ManagementKpiController::class, 'repeatRepairStore'])->name('management_kpi.repeat_repairs.store');
    Route::get('inventory-control', [InventoryControlController::class, 'index'])->name('inventory_control.index');
    Route::get('inventory-control/export', [InventoryControlController::class, 'exportUsage'])->name('inventory_control.export');
    Route::post('inventory-control/stock', [InventoryControlController::class, 'saveStock'])->name('inventory_control.stock.store');
    Route::post('inventory-control/reorder', [InventoryControlController::class, 'createReorder'])->name('inventory_control.reorder.store');
    Route::get('inventory-control', [InventoryControlController::class, 'index'])->name('inventory_control.index');
    Route::get('inventory-control/export', [InventoryControlController::class, 'exportUsage'])->name('inventory_control.export');
    Route::post('inventory-control/stock', [InventoryControlController::class, 'saveStock'])->name('inventory_control.stock.store');
    Route::post('inventory-control/reorder', [InventoryControlController::class, 'createReorder'])->name('inventory_control.reorder.store');


    Route::get('workshop-planning', [WorkshopPlanningController::class, 'index'])->name('workshop_planning.index');
    Route::post('workshop-planning/technician-schedule', [WorkshopPlanningController::class, 'storeTechnicianSchedule'])->name('workshop_planning.technician_schedule.store');
    Route::post('workshop-planning/bay-schedule', [WorkshopPlanningController::class, 'storeBaySchedule'])->name('workshop_planning.bay_schedule.store');
    Route::post('workshop-planning/parts-reservation', [WorkshopPlanningController::class, 'storePartsReservation'])->name('workshop_planning.parts_reservation.store');
    Route::post('workshop-planning/job-plan', [WorkshopPlanningController::class, 'storeJobPlan'])->name('workshop_planning.job_plan.store');

    Route::get('advanced-vehicle-history', [AdvancedVehicleHistoryController::class, 'index'])->name('advanced_vehicle_history.index');
    Route::get('advanced-vehicle-history/{vehicle}', [AdvancedVehicleHistoryController::class, 'show'])->name('advanced_vehicle_history.show');
    Route::get('advanced-vehicle-history/{vehicle}/parts-export', [AdvancedVehicleHistoryController::class, 'exportParts'])->name('advanced_vehicle_history.parts_export');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingsController::class, 'store'])->name('settings.store');
});


/*
|--------------------------------------------------------------------------
| AutoService short URL aliases
|--------------------------------------------------------------------------
| Keep /auto-service as the canonical URL and also support /autoservice
| because the live site/testers use both styles.
*/
Route::prefix('autoservice')->as('autoservice_short.')->middleware(['web','auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('command-centre', [CommandCentreController::class, 'index'])->name('command_centre.index');
    Route::get('command-centre/live', [CommandCentreController::class, 'live'])->name('command_centre.live');
    Route::get('command-center', [CommandCentreController::class, 'index'])->name('command_center.index');
    Route::get('business-intelligence', [BusinessIntelligenceController::class, 'index'])->name('business_intelligence.index');
    Route::get('business-intelligence/export', [BusinessIntelligenceController::class, 'export'])->name('business_intelligence.export');
    Route::get('dealer-enterprise', [DealerEnterpriseController::class, 'index'])->name('dealer_enterprise.index');
    Route::post('dealer-enterprise/fleet-customers', [DealerEnterpriseController::class, 'storeFleetCustomer'])->name('dealer_enterprise.fleet_customers.store');
    Route::post('dealer-enterprise/contracts', [DealerEnterpriseController::class, 'storeContract'])->name('dealer_enterprise.contracts.store');
    Route::post('dealer-enterprise/drivers', [DealerEnterpriseController::class, 'storeDriver'])->name('dealer_enterprise.drivers.store');
    Route::get('dealer-enterprise/fleet-jobs/export', [DealerEnterpriseController::class, 'exportFleetJobs'])->name('dealer_enterprise.export_fleet_jobs');
    Route::get('workspace', [ServiceAdvisorWorkspaceController::class, 'index'])->name('workspace.index');
    Route::resource('receptions', ReceptionController::class)->only(['index','create','store']);
    Route::resource('vehicles', VehicleController::class)->except(['destroy']);
    Route::get('vehicle-search', [VehicleController::class, 'search'])->name('vehicles.search');
    Route::resource('jobs', JobController::class)->except(['destroy']);
    Route::post('jobs/{job}/start', [JobController::class, 'start'])->name('jobs.start');
    Route::post('jobs/{job}/hold', [JobController::class, 'hold'])->name('jobs.hold');
    Route::post('jobs/{job}/complete', [JobController::class, 'complete'])->name('jobs.complete');
    Route::resource('mechanics', MechanicController::class)->except(['show','destroy']);
    Route::resource('packages', ServicePackageController::class)->except(['show','destroy']);
    Route::get('workshop', [WorkshopController::class, 'index'])->name('workshop.index');
    Route::get('parts-labour', [PartsLabourController::class, 'index'])->name('parts_labour.index');
    Route::get('parts-labour/{job}/edit', [PartsLabourController::class, 'edit'])->name('parts_labour.edit');
    Route::get('service-flow', [ServiceFlowController::class, 'index'])->name('service_flow.index');
    Route::get('billing-delivery', [BillingDeliveryController::class, 'index'])->name('billing_delivery.index');
    Route::post('billing-delivery/jobs/{job}/generate-invoice', [BillingDeliveryController::class, 'generateInvoice'])->name('billing_delivery.generate_invoice');
    Route::post('billing-delivery/invoices/{invoice}/mark-paid', [BillingDeliveryController::class, 'markPaid'])->name('billing_delivery.mark_paid');
    Route::post('billing-delivery/jobs/{job}/release', [BillingDeliveryController::class, 'release'])->name('billing_delivery.release');

    Route::post('service-flow/assign', [ServiceFlowController::class, 'assign'])->name('service_flow.assign');
    Route::post('service-flow/inspection', [ServiceFlowController::class, 'inspectionCheckpoint'])->name('service_flow.inspection');
    Route::post('service-flow/jobs/{job}/ready-for-qc', [ServiceFlowController::class, 'readyForQc'])->name('service_flow.ready_for_qc');

    Route::post('parts-labour/{job}/parts', [PartsLabourController::class, 'saveParts'])->name('parts_labour.parts');
    Route::post('parts-labour/{job}/labour', [PartsLabourController::class, 'saveLabour'])->name('parts_labour.labour');
    Route::get('whiteboard', [WhiteboardController::class, 'index'])->name('whiteboard.index');
    Route::get('quality-control', [QualityControlController::class, 'index'])->name('quality_control.index');
    Route::post('quality-control', [QualityControlController::class, 'store'])->name('quality_control.store');
    Route::get('deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::post('deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::get('mechanic-dashboard', [MechanicDashboardController::class, 'index'])->name('mechanic_dashboard.index');
    Route::get('bays', [BayController::class, 'index'])->name('bays.index');
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::resource('appointments', AppointmentController::class)->except(['show','destroy']);
    Route::resource('estimates', EstimateController::class)->except(['destroy']);
    Route::post('estimates/{estimate}/approve', [EstimateController::class, 'approve'])->name('estimates.approve');
    Route::post('estimates/{estimate}/convert-to-job', [EstimateController::class, 'convertToJob'])->name('estimates.convert_to_job');
    Route::resource('inspections', InspectionController::class)->except(['show','destroy']);
    Route::resource('invoices', InvoiceController::class)->except(['destroy']);
    Route::resource('payments', PaymentController::class)->except(['show','destroy']);
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('release-audit', [ReleaseAuditController::class, 'index'])->name('release.audit');
    Route::get('customer-care', [CustomerCareController::class, 'index'])->name('customer_care.index');
    Route::get('maintenance-planner', [MaintenancePlannerController::class, 'index'])->name('maintenance_planner.index');
    Route::post('maintenance-planner', [MaintenancePlannerController::class, 'store'])->name('maintenance_planner.store');
    Route::post('maintenance-planner/{plan}/status', [MaintenancePlannerController::class, 'updateStatus'])->name('maintenance_planner.status');
    Route::get('maintenance-planner/vehicle-health', [MaintenancePlannerController::class, 'vehicleHealth'])->name('maintenance_planner.vehicle_health');



    Route::get('business-intelligence', [BusinessIntelligenceController::class, 'index'])->name('business_intelligence.index');
    Route::get('business-intelligence/export', [BusinessIntelligenceController::class, 'export'])->name('business_intelligence.export');

    Route::get('workshop-planning', [WorkshopPlanningController::class, 'index'])->name('workshop_planning.index');
    Route::post('workshop-planning/technician-schedule', [WorkshopPlanningController::class, 'storeTechnicianSchedule'])->name('workshop_planning.technician_schedule.store');
    Route::post('workshop-planning/bay-schedule', [WorkshopPlanningController::class, 'storeBaySchedule'])->name('workshop_planning.bay_schedule.store');
    Route::post('workshop-planning/parts-reservation', [WorkshopPlanningController::class, 'storePartsReservation'])->name('workshop_planning.parts_reservation.store');
    Route::post('workshop-planning/job-plan', [WorkshopPlanningController::class, 'storeJobPlan'])->name('workshop_planning.job_plan.store');

    Route::get('advanced-vehicle-history', [AdvancedVehicleHistoryController::class, 'index'])->name('advanced_vehicle_history.index');
    Route::get('advanced-vehicle-history/{vehicle}', [AdvancedVehicleHistoryController::class, 'show'])->name('advanced_vehicle_history.show');
    Route::get('advanced-vehicle-history/{vehicle}/parts-export', [AdvancedVehicleHistoryController::class, 'exportParts'])->name('advanced_vehicle_history.parts_export');
    Route::get('completion-check', [CompletionController::class, 'index'])->name('completion.index');
    Route::get('production-audit', [ProductionAuditController::class, 'index'])->name('production_audit.index');
    Route::get('stabilization-centre', [StabilizationController::class, 'index'])->name('stabilization.index');
    Route::get('deployment-diagnostics', [DeploymentDiagnosticsController::class, 'index'])->name('deployment_diagnostics.index');
    Route::get('enterprise-integration', [EnterpriseIntegrationController::class, 'index'])->name('enterprise_integration.index');
    Route::post('enterprise-integration/log-check', [EnterpriseIntegrationController::class, 'logCheck'])->name('enterprise_integration.log_check');
    Route::post('stabilization-centre/issues', [StabilizationController::class, 'storeIssue'])->name('stabilization.issues.store');
    Route::post('stabilization-centre/issues/{id}', [StabilizationController::class, 'updateIssue'])->name('stabilization.issues.update');
});

Route::prefix('autoservice')->as('autoservice_short.')->middleware(['web'])->group(function () {
    Route::get('customer-login', [CustomerPortalController::class, 'customerLogin'])->name('customer_portal.login');
    Route::get('customer-portal/lookup', [CustomerPortalController::class, 'lookup'])->name('customer_portal.lookup');
    Route::get('customer-portal/invoices/{invoice}', [CustomerPortalController::class, 'invoiceDetail'])->name('customer_portal.invoices.show');
    Route::post('customer-portal/feedback', [CustomerPortalController::class, 'submitFeedback'])->name('customer_portal.feedback.store');
    Route::post('customer-portal/appointment-request', [CustomerPortalController::class, 'requestAppointment'])->name('customer_portal.appointment_request.store');
    Route::post('customer-portal/approvals/{approval}/respond', [CustomerPortalController::class, 'respondApproval'])->name('customer_portal.approvals.respond');
    Route::post('customer-portal/alerts/{alert}/acknowledge', [CustomerPortalController::class, 'acknowledgeAlert'])->name('customer_portal.alerts.acknowledge');
});
