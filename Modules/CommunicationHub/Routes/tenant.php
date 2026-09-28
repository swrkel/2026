<?php

use Illuminate\Support\Facades\Route;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubProviderController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubTemplateController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubQueueController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubOtpController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubReportController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubSettingController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubAuditController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubEnterpriseController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubCampaignController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubProductionController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubMarketplaceController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubCertificationController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubCommercialController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubEmailController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubReadinessController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubDiagnosticsController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubEnterpriseExcellenceController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubQualityAssuranceController;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubOperationsController;


// Tenant Communication Hub routes. Loaded with tenancy middleware by RouteServiceProvider.
Route::prefix('communication-hub')->as('communicationhub.')->group(function () {
    Route::get('readiness-check', [CommunicationHubReadinessController::class, 'index'])->name('readiness.index');
    Route::get('diagnostics', [CommunicationHubDiagnosticsController::class, 'index'])->name('diagnostics.index');
    Route::get('production-qa', [CommunicationHubQualityAssuranceController::class, 'index'])->name('quality.index');
    Route::get('operations-support', [CommunicationHubOperationsController::class, 'index'])->name('operations.index');
    Route::get('/', [CommunicationHubController::class, 'dashboard'])->name('dashboard');
    Route::resource('providers', CommunicationHubProviderController::class)->except(['show']);
    Route::post('providers/{provider}/health', [CommunicationHubProviderController::class, 'health'])->name('providers.health');
    Route::post('providers/{provider}/toggle', [CommunicationHubProviderController::class, 'toggle'])->name('providers.toggle');
    Route::resource('templates', CommunicationHubTemplateController::class)->except(['show']);
    Route::get('queue', [CommunicationHubQueueController::class, 'index'])->name('queue.index');
    Route::post('queue/process', [CommunicationHubQueueController::class, 'process'])->name('queue.process');
    Route::post('queue/{message}/retry', [CommunicationHubQueueController::class, 'retry'])->name('queue.retry');
    Route::post('queue/{message}/cancel', [CommunicationHubQueueController::class, 'cancel'])->name('queue.cancel');
    Route::get('otp', [CommunicationHubOtpController::class, 'index'])->name('otp.index');
    Route::post('otp/generate', [CommunicationHubOtpController::class, 'generate'])->name('otp.generate');
    Route::post('otp/verify', [CommunicationHubOtpController::class, 'verify'])->name('otp.verify');


    // Email Platform. The controller and views were present, but these routes
    // were missing, which caused all Email pages/actions to return 404 or
    // RouteNotFoundException.
    Route::prefix('email')->as('email.')->controller(CommunicationHubEmailController::class)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('compose', 'compose')->name('compose');
        Route::post('send', 'send')->name('send');
        Route::get('bulk', 'bulk')->name('bulk');
        Route::post('bulk', 'bulkStore')->name('bulk.store');
        Route::get('queue', 'queue')->name('queue');
        Route::post('{message}/mark-sent', 'markSent')->name('mark_sent');
        Route::post('{message}/mark-failed', 'markFailed')->name('mark_failed');
        Route::post('{message}/retry', 'retry')->name('retry');
    });

    // Backward-compatible GET URLs retained for older bookmarks/menu records.
    Route::get('email-dashboard', fn () => redirect()->route('communicationhub.email.dashboard'));
    Route::get('compose-email', fn () => redirect()->route('communicationhub.email.compose'));
    Route::get('bulk-email', fn () => redirect()->route('communicationhub.email.bulk'));
    Route::get('email-queue', fn () => redirect()->route('communicationhub.email.queue'));
    Route::get('reports', [CommunicationHubReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [CommunicationHubReportController::class, 'export'])->name('reports.export');
    Route::get('settings', [CommunicationHubSettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [CommunicationHubSettingController::class, 'store'])->name('settings.store');

    Route::get('enterprise-centre', [CommunicationHubEnterpriseController::class, 'index'])->name('enterprise.index');
    Route::get('provider-monitor', [CommunicationHubEnterpriseController::class, 'providerMonitor'])->name('enterprise.provider_monitor');
    Route::get('queue-monitor', [CommunicationHubEnterpriseController::class, 'queueMonitor'])->name('enterprise.queue_monitor');
    Route::get('template-designer', [CommunicationHubEnterpriseController::class, 'templateDesigner'])->name('enterprise.template_designer');
    Route::get('otp-centre', [CommunicationHubEnterpriseController::class, 'otpCentre'])->name('enterprise.otp_centre');
    Route::get('communication-audit', [CommunicationHubEnterpriseController::class, 'communicationAudit'])->name('enterprise.communication_audit');
    Route::resource('campaigns', CommunicationHubCampaignController::class)->except(['show']);
    Route::post('campaigns/{campaign}/schedule', [CommunicationHubCampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/cancel', [CommunicationHubCampaignController::class, 'cancel'])->name('campaigns.cancel');
    Route::get('standalone-audit', [CommunicationHubAuditController::class, 'index'])->name('audit.index');


    Route::get('marketplace', [CommunicationHubMarketplaceController::class, 'index'])->name('marketplace.index');
    Route::post('marketplace/{package}/install', [CommunicationHubMarketplaceController::class, 'install'])->name('marketplace.install');
    Route::post('marketplace/{package}/enable', [CommunicationHubMarketplaceController::class, 'enable'])->name('marketplace.enable');
    Route::post('marketplace/{package}/disable', [CommunicationHubMarketplaceController::class, 'disable'])->name('marketplace.disable');
    Route::post('marketplace/{package}/sandbox', [CommunicationHubMarketplaceController::class, 'sandbox'])->name('marketplace.sandbox');

    Route::get('production-hardening', [CommunicationHubProductionController::class, 'index'])->name('production.index');
    Route::get('production-hardening/standalone', [CommunicationHubProductionController::class, 'standalone'])->name('production.standalone');
    Route::get('production-hardening/security', [CommunicationHubProductionController::class, 'security'])->name('production.security');


    Route::get('enterprise-certification', [CommunicationHubCertificationController::class, 'index'])->name('certification.index');
    Route::get('enterprise-certification/standalone', [CommunicationHubCertificationController::class, 'standalone'])->name('certification.standalone');
    Route::get('enterprise-certification/security', [CommunicationHubCertificationController::class, 'security'])->name('certification.security');
    Route::get('enterprise-certification/api', [CommunicationHubCertificationController::class, 'api'])->name('certification.api');
    Route::get('enterprise-certification/release-notes', [CommunicationHubCertificationController::class, 'releaseNotes'])->name('certification.release_notes');


    // Commercial SMS selling platform UI pages. These are intentionally module-owned
    // routes so the main sidebar does not need hardcoded Communication Hub submenu links.
    Route::prefix('commercial')->as('commercial.')->controller(CommunicationHubCommercialController::class)->group(function () {
        Route::get('sms-dashboard', 'smsDashboard')->name('sms_dashboard');
        Route::get('send-sms', 'sendSms')->name('send_sms');
        Route::post('send-sms', 'sendSmsStore')->name('send_sms.store');
        Route::get('bulk-sms', 'bulkSms')->name('bulk_sms');
        Route::post('bulk-sms', 'bulkSmsStore')->name('bulk_sms.store');
        Route::get('scheduled-sms', 'scheduledSms')->name('scheduled_sms');
        Route::post('scheduled-sms', 'scheduledSmsStore')->name('scheduled_sms.store');
        Route::post('messages/process-pending', 'processPendingMessages')->name('messages.process_pending');
        Route::post('messages/{message}/mark-sent', 'markMessageSent')->name('messages.mark_sent');
        Route::post('messages/{message}/mark-failed', 'markMessageFailed')->name('messages.mark_failed');
        Route::post('messages/{message}/retry', 'retryMessage')->name('messages.retry');
        Route::get('sms-packages', 'smsPackages')->name('sms_packages');
        Route::post('sms-packages', 'smsPackagesStore')->name('sms_packages.store');
        Route::get('business-wallets', 'businessWallets')->name('business_wallets');
        Route::get('credit-refills', 'creditRefills')->name('credit_refills');
        Route::post('credit-refills', 'creditRefillsStore')->name('credit_refills.store');
        // Compatibility aliases used by earlier dashboard/menu views. Keep these to prevent RouteNotFoundException.
        Route::get('refills', 'creditRefills')->name('refills');
        Route::post('refills', 'creditRefillsStore')->name('refills.store');
        Route::get('credit-transactions', 'creditTransactions')->name('credit_transactions');
        Route::get('sms-clients', 'smsClients')->name('sms_clients');
        Route::post('sms-clients', 'smsClientsStore')->name('sms_clients.store');
        Route::get('reseller-dashboard', 'resellerDashboard')->name('reseller_dashboard');
        Route::get('api-tokens', 'apiTokens')->name('api_tokens');
        Route::post('api-tokens', 'apiTokensStore')->name('api_tokens.store');
        Route::get('api-logs', 'apiLogs')->name('api_logs');
        Route::get('api-documentation', 'apiDocumentation')->name('api_documentation');

        Route::get('whatsapp-dashboard', 'whatsappDashboard')->name('whatsapp_dashboard');
        Route::get('send-whatsapp', 'sendWhatsapp')->name('send_whatsapp');
        Route::post('send-whatsapp', 'sendWhatsappStore')->name('send_whatsapp.store');
        Route::get('bulk-whatsapp', 'bulkWhatsapp')->name('bulk_whatsapp');
        Route::post('bulk-whatsapp', 'bulkWhatsappStore')->name('bulk_whatsapp.store');
        Route::get('scheduled-whatsapp', 'scheduledWhatsapp')->name('scheduled_whatsapp');
        Route::post('scheduled-whatsapp', 'scheduledWhatsappStore')->name('scheduled_whatsapp.store');
        Route::get('whatsapp-templates', 'whatsappTemplates')->name('whatsapp_templates');
        Route::post('whatsapp-templates', 'whatsappTemplatesStore')->name('whatsapp_templates.store');
        Route::get('whatsapp-profiles', 'whatsappProfiles')->name('whatsapp_profiles');
        Route::post('whatsapp-profiles', 'whatsappProfilesStore')->name('whatsapp_profiles.store');

        Route::get('push-dashboard', 'pushDashboard')->name('push_dashboard');
        Route::get('send-push', 'sendPush')->name('send_push');
        Route::post('send-push', 'sendPushStore')->name('send_push.store');
        Route::get('bulk-push', 'bulkPush')->name('bulk_push');
        Route::post('bulk-push', 'bulkPushStore')->name('bulk_push.store');
        Route::get('scheduled-push', 'scheduledPush')->name('scheduled_push');
        Route::post('scheduled-push', 'scheduledPushStore')->name('scheduled_push.store');
        Route::get('push-devices', 'pushDevices')->name('push_devices');
        Route::post('push-devices', 'pushDevicesStore')->name('push_devices.store');
        Route::get('push-templates', 'pushTemplates')->name('push_templates');
        Route::post('push-templates', 'pushTemplatesStore')->name('push_templates.store');
        Route::get('in-app-dashboard', 'inAppDashboard')->name('in_app_dashboard');
        Route::get('send-in-app', 'sendInApp')->name('send_in_app');
        Route::post('send-in-app', 'sendInAppStore')->name('send_in_app.store');
        Route::get('in-app-inbox', 'inAppInbox')->name('in_app_inbox');
        Route::post('in-app/{notification}/mark-read', 'markInAppRead')->name('in_app.mark_read');
        Route::post('in-app/{notification}/archive', 'archiveInApp')->name('in_app.archive');
        Route::get('in-app-templates', 'inAppTemplates')->name('in_app_templates');
        Route::post('in-app-templates', 'inAppTemplatesStore')->name('in_app_templates.store');

        Route::get('chat-dashboard', 'chatDashboard')->name('chat_dashboard');
        Route::get('live-chat', 'liveChat')->name('live_chat');
        Route::post('live-chat/conversations', 'liveChatConversationStore')->name('live_chat.conversations.store');
        Route::post('live-chat/messages', 'liveChatMessageStore')->name('live_chat.messages.store');
        Route::post('live-chat/{conversation}/close', 'closeLiveChatConversation')->name('live_chat.close');
        Route::get('internal-messaging', 'internalMessaging')->name('internal_messaging');
        Route::post('internal-messaging', 'internalMessageStore')->name('internal_messaging.store');
        Route::post('internal-messaging/{message}/mark-read', 'markInternalMessageRead')->name('internal_messaging.mark_read');
        Route::get('chat-templates', 'chatTemplates')->name('chat_templates');
        Route::post('chat-templates', 'chatTemplatesStore')->name('chat_templates.store');

        Route::get('automation-dashboard', 'automationDashboard')->name('automation_dashboard');
        Route::get('automation-rules', 'automationRules')->name('automation_rules');
        Route::post('automation-rules', 'automationRulesStore')->name('automation_rules.store');
        Route::post('automation-rules/{rule}/toggle', 'toggleAutomationRule')->name('automation_rules.toggle');
        Route::post('automation-rules/{rule}/delete', 'deleteAutomationRule')->name('automation_rules.delete');
        Route::get('automation-events', 'automationEvents')->name('automation_events');
        Route::post('automation-events/test', 'automationTestEvent')->name('automation_events.test');
        Route::post('automation-events/process-pending', 'processAutomationPending')->name('automation_events.process_pending');
        Route::post('automation-events/{event}/execute', 'executeAutomationEvent')->name('automation_events.execute');

        Route::get('workflow-dashboard', 'workflowDashboard')->name('workflow_dashboard');
        Route::get('workflow-events', 'workflowEvents')->name('workflow_events');
        Route::post('workflow-events', 'workflowEventsStore')->name('workflow_events.store');
        Route::post('workflow-events/{event}/toggle', 'toggleWorkflowEvent')->name('workflow_events.toggle');
        Route::get('workflow-rules', 'workflowRules')->name('workflow_rules');
        Route::post('workflow-rules', 'workflowRulesStore')->name('workflow_rules.store');
        Route::post('workflow-rules/{rule}/toggle', 'toggleWorkflowRule')->name('workflow_rules.toggle');
        Route::post('workflow-rules/{rule}/delete', 'deleteWorkflowRule')->name('workflow_rules.delete');
        Route::get('workflow-event-log', 'workflowEventLog')->name('workflow_event_log');
        Route::post('workflow-event-log/test', 'workflowTestEvent')->name('workflow_event_log.test');
        Route::post('workflow-event-log/{log}/process', 'processWorkflowLog')->name('workflow_event_log.process');

        Route::get('analytics-dashboard', 'analyticsDashboard')->name('analytics_dashboard');
        Route::get('message-history-report', 'messageHistoryReport')->name('message_history_report');
        Route::get('provider-performance-report', 'providerPerformanceReport')->name('provider_performance_report');
        Route::get('campaign-performance-report', 'campaignPerformanceReport')->name('campaign_performance_report');
        Route::get('api-gateway-dashboard', 'apiGatewayDashboard')->name('api_gateway_dashboard');
        Route::post('api-gateway/tokens', 'createGatewayToken')->name('api_gateway.tokens.store');
        Route::get('communication-audit-centre', 'communicationAuditCentre')->name('communication_audit_centre');
        Route::get('delivery-reports', 'deliveryReports')->name('delivery_reports');
        Route::get('profit-reports', 'profitReports')->name('profit_reports');
    });

    // Stage 017 - Enterprise Excellence final production-grade pages.
    Route::prefix('enterprise-excellence')->as('excellence.')->controller(CommunicationHubEnterpriseExcellenceController::class)->group(function () {
        Route::get('executive-centre', 'executiveCentre')->name('executive_centre');
        Route::get('notification-centre', 'notificationCentre')->name('notifications.index');
        Route::post('notification-centre', 'notificationStore')->name('notifications.store');
        Route::get('event-registry', 'eventRegistry')->name('events.index');
        Route::post('event-registry', 'eventStore')->name('events.store');
        Route::get('provider-health', 'providerHealth')->name('provider_health.index');
        Route::post('provider-health', 'providerHealthStore')->name('provider_health.store');
        Route::get('cost-centre', 'costCentre')->name('cost_centre');
        Route::post('cost-centre', 'costStore')->name('costs.store');
        Route::get('advanced-scheduler', 'advancedScheduler')->name('advanced_scheduler');
        Route::post('advanced-scheduler', 'scheduleStore')->name('schedules.store');
        Route::get('public-api-platform', 'publicApiPlatform')->name('public_api_platform');
        Route::post('public-api-platform/clients', 'apiClientStore')->name('api_clients.store');
        Route::get('mobile-hooks', 'mobileHooks')->name('mobile_hooks');
        Route::get('marketplace-architecture', 'marketplaceArchitecture')->name('marketplace_architecture');
        Route::get('final-audit', 'finalAudit')->name('final_audit');
    });

});
