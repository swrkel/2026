@php
    $communicationHubMenuOpen = request()->is('communication-hub*');

    $communicationHubItems = config('communicationhub_menu.items');
    if (empty($communicationHubItems) || !is_array($communicationHubItems)) {
        $communicationHubItems = [
            ['title' => 'Dashboard', 'route' => 'communicationhub.dashboard', 'url' => url('communication-hub'), 'icon' => 'fa fa-dashboard'],
            ['title' => '--- SMS Selling Platform ---', 'header' => true],
            ['title' => 'SMS Dashboard', 'route' => 'communicationhub.commercial.sms_dashboard', 'url' => url('communication-hub/commercial/sms-dashboard'), 'icon' => 'fa fa-line-chart'],
            ['title' => 'Send SMS', 'route' => 'communicationhub.commercial.send_sms', 'url' => url('communication-hub/commercial/send-sms'), 'icon' => 'fa fa-paper-plane'],
            ['title' => 'Bulk SMS', 'route' => 'communicationhub.commercial.bulk_sms', 'url' => url('communication-hub/commercial/bulk-sms'), 'icon' => 'fa fa-users'],
            ['title' => 'Scheduled SMS', 'route' => 'communicationhub.commercial.scheduled_sms', 'url' => url('communication-hub/commercial/scheduled-sms'), 'icon' => 'fa fa-clock-o'],
            ['title' => 'SMS Packages', 'route' => 'communicationhub.commercial.sms_packages', 'url' => url('communication-hub/commercial/sms-packages'), 'icon' => 'fa fa-archive'],
            ['title' => 'Business Wallets', 'route' => 'communicationhub.commercial.business_wallets', 'url' => url('communication-hub/commercial/business-wallets'), 'icon' => 'fa fa-credit-card'],
            ['title' => 'Credit Refills', 'route' => 'communicationhub.commercial.credit_refills', 'url' => url('communication-hub/commercial/credit-refills'), 'icon' => 'fa fa-plus-circle'],
            ['title' => 'Credit Transactions', 'route' => 'communicationhub.commercial.credit_transactions', 'url' => url('communication-hub/commercial/credit-transactions'), 'icon' => 'fa fa-exchange'],
            ['title' => 'SMS Clients', 'route' => 'communicationhub.commercial.sms_clients', 'url' => url('communication-hub/commercial/sms-clients'), 'icon' => 'fa fa-building'],
            ['title' => 'Reseller Dashboard', 'route' => 'communicationhub.commercial.reseller_dashboard', 'url' => url('communication-hub/commercial/reseller-dashboard'), 'icon' => 'fa fa-sitemap'],
            ['title' => 'API Tokens', 'route' => 'communicationhub.commercial.api_tokens', 'url' => url('communication-hub/commercial/api-tokens'), 'icon' => 'fa fa-key'],
            ['title' => 'API Logs', 'route' => 'communicationhub.commercial.api_logs', 'url' => url('communication-hub/commercial/api-logs'), 'icon' => 'fa fa-list-alt'],
            ['title' => 'API Documentation', 'route' => 'communicationhub.commercial.api_documentation', 'url' => url('communication-hub/commercial/api-documentation'), 'icon' => 'fa fa-book'],
            ['title' => 'Delivery Reports', 'route' => 'communicationhub.commercial.delivery_reports', 'url' => url('communication-hub/commercial/delivery-reports'), 'icon' => 'fa fa-truck'],
            ['title' => 'SMS Profit Reports', 'route' => 'communicationhub.commercial.profit_reports', 'url' => url('communication-hub/commercial/profit-reports'), 'icon' => 'fa fa-money'],
            ['title' => '--- Email Business Platform ---', 'header' => true],
            ['title' => 'Email Dashboard', 'route' => 'communicationhub.email.dashboard', 'url' => url('communication-hub/email'), 'icon' => 'fa fa-envelope'],
            ['title' => 'Compose Email', 'route' => 'communicationhub.email.compose', 'url' => url('communication-hub/email/compose'), 'icon' => 'fa fa-pencil'],
            ['title' => 'Bulk Email', 'route' => 'communicationhub.email.bulk', 'url' => url('communication-hub/email/bulk'), 'icon' => 'fa fa-users'],
            ['title' => 'Email Queue', 'route' => 'communicationhub.email.queue', 'url' => url('communication-hub/email/queue'), 'icon' => 'fa fa-list'],
            ['title' => '--- WhatsApp Business Platform ---', 'header' => true],
            ['title' => 'WhatsApp Dashboard', 'route' => 'communicationhub.commercial.whatsapp_dashboard', 'url' => url('communication-hub/commercial/whatsapp-dashboard'), 'icon' => 'fa fa-whatsapp'],
            ['title' => 'Send WhatsApp', 'route' => 'communicationhub.commercial.send_whatsapp', 'url' => url('communication-hub/commercial/send-whatsapp'), 'icon' => 'fa fa-paper-plane'],
            ['title' => 'Bulk WhatsApp', 'route' => 'communicationhub.commercial.bulk_whatsapp', 'url' => url('communication-hub/commercial/bulk-whatsapp'), 'icon' => 'fa fa-users'],
            ['title' => 'Scheduled WhatsApp', 'route' => 'communicationhub.commercial.scheduled_whatsapp', 'url' => url('communication-hub/commercial/scheduled-whatsapp'), 'icon' => 'fa fa-clock-o'],
            ['title' => 'WhatsApp Templates', 'route' => 'communicationhub.commercial.whatsapp_templates', 'url' => url('communication-hub/commercial/whatsapp-templates'), 'icon' => 'fa fa-file-text-o'],
            ['title' => 'WhatsApp Profiles', 'route' => 'communicationhub.commercial.whatsapp_profiles', 'url' => url('communication-hub/commercial/whatsapp-profiles'), 'icon' => 'fa fa-cogs'],
            ['title' => '--- Push Notification Platform ---', 'header' => true],
            ['title' => 'Push Dashboard', 'route' => 'communicationhub.commercial.push_dashboard', 'url' => url('communication-hub/commercial/push-dashboard'), 'icon' => 'fa fa-bell'],
            ['title' => 'Send Push', 'route' => 'communicationhub.commercial.send_push', 'url' => url('communication-hub/commercial/send-push'), 'icon' => 'fa fa-paper-plane'],
            ['title' => 'Bulk Push', 'route' => 'communicationhub.commercial.bulk_push', 'url' => url('communication-hub/commercial/bulk-push'), 'icon' => 'fa fa-bullhorn'],
            ['title' => 'Scheduled Push', 'route' => 'communicationhub.commercial.scheduled_push', 'url' => url('communication-hub/commercial/scheduled-push'), 'icon' => 'fa fa-clock-o'],
            ['title' => 'Push Devices', 'route' => 'communicationhub.commercial.push_devices', 'url' => url('communication-hub/commercial/push-devices'), 'icon' => 'fa fa-mobile'],
            ['title' => 'Push Templates', 'route' => 'communicationhub.commercial.push_templates', 'url' => url('communication-hub/commercial/push-templates'), 'icon' => 'fa fa-file-text-o'],
            ['title' => '--- Workflow Event Engine ---', 'header' => true],
            ['title' => 'Workflow Dashboard', 'route' => 'communicationhub.commercial.workflow_dashboard', 'url' => url('communication-hub/commercial/workflow-dashboard'), 'icon' => 'fa fa-random'],
            ['title' => 'Workflow Events', 'route' => 'communicationhub.commercial.workflow_events', 'url' => url('communication-hub/commercial/workflow-events'), 'icon' => 'fa fa-code-fork'],
            ['title' => 'Workflow Rules', 'route' => 'communicationhub.commercial.workflow_rules', 'url' => url('communication-hub/commercial/workflow-rules'), 'icon' => 'fa fa-magic'],
            ['title' => 'Workflow Event Log', 'route' => 'communicationhub.commercial.workflow_event_log', 'url' => url('communication-hub/commercial/workflow-event-log'), 'icon' => 'fa fa-history'],
            ['title' => '--- Core Communication Hub ---', 'header' => true],
            ['title' => 'Providers', 'route' => 'communicationhub.providers.index', 'url' => url('communication-hub/providers'), 'icon' => 'fa fa-plug'],
            ['title' => 'Templates', 'route' => 'communicationhub.templates.index', 'url' => url('communication-hub/templates'), 'icon' => 'fa fa-file-text-o'],
            ['title' => 'Campaigns', 'route' => 'communicationhub.campaigns.index', 'url' => url('communication-hub/campaigns'), 'icon' => 'fa fa-bullhorn'],
            ['title' => 'Message Queue', 'route' => 'communicationhub.queue.index', 'url' => url('communication-hub/queue'), 'icon' => 'fa fa-list'],
            ['title' => 'OTP Center', 'route' => 'communicationhub.otp.index', 'url' => url('communication-hub/otp'), 'icon' => 'fa fa-key'],
            ['title' => 'Reports', 'route' => 'communicationhub.reports.index', 'url' => url('communication-hub/reports'), 'icon' => 'fa fa-bar-chart'],
            ['title' => 'Settings', 'route' => 'communicationhub.settings.index', 'url' => url('communication-hub/settings'), 'icon' => 'fa fa-cog'],
            ['title' => 'Standalone Audit', 'route' => 'communicationhub.audit.index', 'url' => url('communication-hub/standalone-audit'), 'icon' => 'fa fa-check-square-o'],
            ['title' => 'Production Hardening', 'route' => 'communicationhub.production.index', 'url' => url('communication-hub/production-hardening'), 'icon' => 'fa fa-shield'],
            ['title' => 'Marketplace', 'route' => 'communicationhub.marketplace.index', 'url' => url('communication-hub/marketplace'), 'icon' => 'fa fa-puzzle-piece'],
            ['title' => 'Readiness Check', 'route' => 'communicationhub.readiness.index', 'url' => url('communication-hub/readiness-check'), 'icon' => 'fa fa-check-circle'],
            ['title' => 'Diagnostics Centre', 'route' => 'communicationhub.diagnostics.index', 'url' => url('communication-hub/diagnostics'), 'icon' => 'fa fa-stethoscope'],
            ['title' => 'Enterprise Certification', 'route' => 'communicationhub.certification.index', 'url' => url('communication-hub/enterprise-certification'), 'icon' => 'fa fa-certificate'],
        ];
    }

    $canSeeCommunicationHub = false;
    try {
        $canSeeCommunicationHub = \App\Utils\SidebarPermissionUtil::isVisibleInSidebar('communication_hub');
    } catch (\Throwable $e) {
        $canSeeCommunicationHub = false;
    }

    if ($canSeeCommunicationHub && auth()->check()) {
        try {
            $canSeeCommunicationHub = auth()->user()->can('communicationhub.dashboard.view')
                || auth()->user()->can('communicationhub.access')
                || auth()->user()->can('superadmin')
                || auth()->user()->hasRole('Admin#' . session('business.id'))
                || auth()->user()->hasRole('Superadmin')
                || auth()->user()->hasRole('Super Admin');
        } catch (\Throwable $e) {
            $canSeeCommunicationHub = true;
        }
    }
@endphp

@if($canSeeCommunicationHub)
<li class="nav-item {{ $communicationHubMenuOpen ? 'active active-sub' : '' }}" data-sidebar-module="communication_hub">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#communication-hub-menu" aria-expanded="{{ $communicationHubMenuOpen ? 'true' : 'false' }}" aria-controls="communication-hub-menu">
        <i class="fa fa-comments"></i>
        <span>Communication Hub</span>
    </a>
    <div id="communication-hub-menu" class="collapse {{ $communicationHubMenuOpen ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            @foreach($communicationHubItems as $item)
                @if(!empty($item['header']))
                    <h6 class="collapse-header">{{ str_replace(['---'], '', $item['title'] ?? '') }}</h6>
                    @continue
                @endif

                @php
                    $communicationHubUrl = !empty($item['route']) && Route::has($item['route'])
                        ? route($item['route'])
                        : ($item['url'] ?? '#');
                @endphp
                <a class="collapse-item {{ request()->url() === $communicationHubUrl ? 'active active-sub' : '' }}" href="{{ $communicationHubUrl }}">
                    @if(!empty($item['icon']))<i class="{{ $item['icon'] }}"></i> @endif{{ $item['title'] ?? '' }}
                </a>
            @endforeach
        </div>
    </div>
</li>
@endif
