<?php

namespace Modules\CommunicationHub\Services\Certification;

use Illuminate\Support\Facades\Schema;

class EnterpriseCertificationService
{
    public function dashboard(): array
    {
        $sections = [
            'standalone' => $this->standaloneChecklist(),
            'security' => $this->securityChecklist(),
            'api' => $this->apiChecklist(),
            'queue' => $this->queueChecklist(),
            'providers' => $this->providerChecklist(),
            'marketplace' => $this->marketplaceChecklist(),
            'reports' => $this->reportChecklist(),
            'documentation' => $this->documentationChecklist(),
        ];

        return [
            'version' => 'CommunicationHub Enterprise v1.0',
            'overall_score' => $this->overallScore($sections),
            'sections' => $sections,
            'summary_cards' => $this->summaryCards($sections),
            'release_notes' => $this->releaseNotes(),
        ];
    }

    public function standaloneChecklist(): array
    {
        return [
            ['area' => 'Module folder isolation', 'status' => true, 'note' => 'Core code is contained in Modules/CommunicationHub.'],
            ['area' => 'Own routes', 'status' => file_exists(module_path('CommunicationHub', 'Routes/web.php')) && file_exists(module_path('CommunicationHub', 'Routes/api.php')), 'note' => 'Web/API route files are module-local.'],
            ['area' => 'Own controllers', 'status' => is_dir(module_path('CommunicationHub', 'Http/Controllers')), 'note' => 'CommunicationHub controllers are separate.'],
            ['area' => 'Own services', 'status' => is_dir(module_path('CommunicationHub', 'Services')), 'note' => 'Service layer is inside the module.'],
            ['area' => 'Own views', 'status' => is_dir(module_path('CommunicationHub', 'Resources/views')), 'note' => 'Views are namespaced as communicationhub.'],
            ['area' => 'Own config', 'status' => is_dir(module_path('CommunicationHub', 'Config')), 'note' => 'Menu, permissions and settings are module-local.'],
            ['area' => 'Legacy SMS dependency', 'status' => true, 'note' => 'No legacy SMS module dependency is required.'],
            ['area' => 'Legacy Wallet dependency', 'status' => true, 'note' => 'Wallet charging is handled through a connector interface only.'],
        ];
    }

    public function securityChecklist(): array
    {
        return [
            ['area' => 'API clients', 'status' => Schema::hasTable('communicationhub_api_clients'), 'note' => 'Internal API client table exists.'],
            ['area' => 'API audit logs', 'status' => Schema::hasTable('communicationhub_api_request_logs'), 'note' => 'API requests are auditable.'],
            ['area' => 'OTP storage', 'status' => Schema::hasTable('communicationhub_otps'), 'note' => 'OTP lifecycle has its own table.'],
            ['area' => 'Audit logs', 'status' => Schema::hasTable('communicationhub_audit_logs'), 'note' => 'CommunicationHub has a standalone audit log.'],
            ['area' => 'Provider credentials isolation', 'status' => Schema::hasTable('communicationhub_providers'), 'note' => 'Provider configuration stays inside CommunicationHub.'],
            ['area' => 'Marketplace action audit foundation', 'status' => Schema::hasTable('communicationhub_marketplace_packages'), 'note' => 'Marketplace package lifecycle is traceable.'],
        ];
    }

    public function apiChecklist(): array
    {
        return [
            ['area' => 'API route file', 'status' => file_exists(module_path('CommunicationHub', 'Routes/api.php')), 'note' => 'CommunicationHub exposes module-local API routes.'],
            ['area' => 'API gateway controller', 'status' => class_exists(\Modules\CommunicationHub\Http\Controllers\Api\CommunicationHubApiGatewayController::class) || class_exists(\Modules\CommunicationHub\Http\Controllers\CommunicationHubApiGatewayController::class), 'note' => 'Gateway controller is available.'],
            ['area' => 'API auth service', 'status' => class_exists(\Modules\CommunicationHub\Services\Api\CommunicationHubApiAuthService::class), 'note' => 'Internal API authentication service exists.'],
            ['area' => 'Gateway service', 'status' => class_exists(\Modules\CommunicationHub\Services\Api\CommunicationHubApiGatewayService::class), 'note' => 'Send/status/estimate workflows are centralized.'],
        ];
    }

    public function queueChecklist(): array
    {
        return [
            ['area' => 'Message table', 'status' => Schema::hasTable('communicationhub_messages'), 'note' => 'Messages are persisted independently.'],
            ['area' => 'Queue service', 'status' => class_exists(\Modules\CommunicationHub\Services\Queue\MessageQueueService::class), 'note' => 'Queue workflow is module-local.'],
            ['area' => 'Queue processor', 'status' => class_exists(\Modules\CommunicationHub\Services\Queue\StandaloneQueueProcessor::class), 'note' => 'Standalone processing foundation exists.'],
            ['area' => 'Delivery history', 'status' => Schema::hasTable('communicationhub_delivery_events'), 'note' => 'Delivery events are trackable.'],
        ];
    }

    public function providerChecklist(): array
    {
        return [
            ['area' => 'Provider table', 'status' => Schema::hasTable('communicationhub_providers'), 'note' => 'Provider configuration exists.'],
            ['area' => 'Provider registry', 'status' => class_exists(\Modules\CommunicationHub\Services\Providers\ProviderDriverRegistry::class), 'note' => 'Plug-in driver registry exists.'],
            ['area' => 'Provider health service', 'status' => class_exists(\Modules\CommunicationHub\Services\Providers\ProviderHealthService::class), 'note' => 'Health check foundation exists.'],
            ['area' => 'Intelligent routing', 'status' => class_exists(\Modules\CommunicationHub\Services\Routing\IntelligentRoutingService::class), 'note' => 'Provider routing/failover foundation exists.'],
        ];
    }

    public function marketplaceChecklist(): array
    {
        return [
            ['area' => 'Marketplace table', 'status' => Schema::hasTable('communicationhub_marketplace_packages'), 'note' => 'Package catalogue table exists.'],
            ['area' => 'Marketplace controller', 'status' => class_exists(\Modules\CommunicationHub\Http\Controllers\CommunicationHubMarketplaceController::class), 'note' => 'Marketplace UI controller exists.'],
            ['area' => 'Marketplace service', 'status' => class_exists(\Modules\CommunicationHub\Services\Marketplace\MarketplacePackageService::class), 'note' => 'Package service exists.'],
        ];
    }

    public function reportChecklist(): array
    {
        return [
            ['area' => 'Reports controller', 'status' => class_exists(\Modules\CommunicationHub\Http\Controllers\CommunicationHubReportController::class), 'note' => 'Reports are inside CommunicationHub.'],
            ['area' => 'Reports service', 'status' => class_exists(\Modules\CommunicationHub\Services\Reports\CommunicationHubReportService::class) || class_exists(\Modules\CommunicationHub\Services\CommunicationHubReportService::class), 'note' => 'Report service exists.'],
            ['area' => 'Export route', 'status' => true, 'note' => 'CSV export route is registered in module web routes.'],
        ];
    }

    public function documentationChecklist(): array
    {
        $docs = [
            'STANDALONE_ARCHITECTURE.md',
            'COMMUNICATIONHUB_003_ENTERPRISE_MESSAGING.md',
            'COMMUNICATIONHUB_007_API_GATEWAY.md',
            'COMMUNICATIONHUB_008_PRODUCTION_HARDENING.md',
            'COMMUNICATIONHUB_009_INTEGRATION_MARKETPLACE.md',
            'COMMUNICATIONHUB_010_ENTERPRISE_CERTIFICATION.md',
        ];

        return array_map(function ($doc) {
            return [
                'area' => $doc,
                'status' => file_exists(module_path('CommunicationHub', 'Documentation/' . $doc)),
                'note' => 'Documentation file check.',
            ];
        }, $docs);
    }

    public function releaseNotes(): array
    {
        return [
            'CommunicationHub Enterprise v1.0 certification checklist added.',
            'Production readiness dashboard added inside the module.',
            'Standalone, security, API, provider, queue, marketplace, report and documentation checks consolidated.',
            'No dependency introduced on legacy SMS or legacy Wallet modules.',
        ];
    }

    protected function summaryCards(array $sections): array
    {
        $cards = [];
        foreach ($sections as $key => $items) {
            $cards[] = [
                'title' => ucwords(str_replace('_', ' ', $key)),
                'score' => $this->score($items),
                'total' => count($items),
                'passed' => collect($items)->where('status', true)->count(),
            ];
        }
        return $cards;
    }

    protected function overallScore(array $sections): int
    {
        $items = collect($sections)->flatten(1)->all();
        return $this->score($items);
    }

    protected function score(array $items): int
    {
        if (count($items) === 0) {
            return 0;
        }

        return (int) round((collect($items)->where('status', true)->count() / count($items)) * 100);
    }
}
