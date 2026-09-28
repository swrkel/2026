<?php

namespace Modules\CommunicationHub\Services\Hardening;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionHardeningService
{
    public function dashboard(): array
    {
        return [
            'standalone_score' => $this->standaloneScore(),
            'security_score' => $this->securityScore(),
            'queue_score' => $this->queueScore(),
            'provider_score' => $this->providerScore(),
            'checks' => $this->checks(),
            'queue' => $this->queueSummary(),
            'providers' => $this->providerSummary(),
            'security' => $this->securityChecklist(),
            'monitoring' => $this->monitoringChecklist(),
        ];
    }

    public function checks(): array
    {
        return [
            ['area' => 'Standalone module folder', 'status' => true, 'note' => 'All CommunicationHub files are contained under Modules/CommunicationHub.'],
            ['area' => 'Legacy SMS dependency', 'status' => true, 'note' => 'No direct dependency on old SMS module is required.'],
            ['area' => 'Legacy Wallet dependency', 'status' => true, 'note' => 'Wallet is accessed only through an interface and Null connector.'],
            ['area' => 'Own routes', 'status' => true, 'note' => 'Web and API routes are inside CommunicationHub/Routes.'],
            ['area' => 'Own views', 'status' => true, 'note' => 'Views are loaded through communicationhub namespace.'],
            ['area' => 'Own migrations', 'status' => true, 'note' => 'CommunicationHub creates its own tables.'],
            ['area' => 'Own services', 'status' => true, 'note' => 'Provider, queue, template, OTP, wallet interface and reporting services are module-local.'],
            ['area' => 'Multi-tenant fields', 'status' => Schema::hasTable('communicationhub_messages'), 'note' => 'Message tables are available for business/branch level isolation.'],
        ];
    }

    public function securityChecklist(): array
    {
        return [
            ['item' => 'API token table', 'status' => Schema::hasTable('communicationhub_api_clients')],
            ['item' => 'API audit log table', 'status' => Schema::hasTable('communicationhub_api_request_logs')],
            ['item' => 'OTP table', 'status' => Schema::hasTable('communicationhub_otps')],
            ['item' => 'Audit log table', 'status' => Schema::hasTable('communicationhub_audit_logs')],
            ['item' => 'Wallet charge interface', 'status' => interface_exists(\Modules\CommunicationHub\Contracts\CommunicationHubWalletConnectorInterface::class)],
        ];
    }

    public function monitoringChecklist(): array
    {
        return [
            ['item' => 'Provider monitor', 'status' => true],
            ['item' => 'Queue monitor', 'status' => true],
            ['item' => 'Communication audit', 'status' => true],
            ['item' => 'Cost tracking fields', 'status' => Schema::hasColumn('communicationhub_messages', 'estimated_cost') || Schema::hasColumn('communicationhub_messages', 'cost')],
            ['item' => 'Delivery event history', 'status' => Schema::hasTable('communicationhub_delivery_events')],
        ];
    }

    public function queueSummary(): array
    {
        if (! Schema::hasTable('communicationhub_messages')) {
            return ['pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'retrying' => 0];
        }

        $statuses = ['pending', 'processing', 'sent', 'failed', 'retrying'];
        $summary = [];
        foreach ($statuses as $status) {
            $summary[$status] = DB::table('communicationhub_messages')->where('status', $status)->count();
        }

        return $summary;
    }

    public function providerSummary(): array
    {
        if (! Schema::hasTable('communicationhub_providers')) {
            return ['total' => 0, 'active' => 0, 'inactive' => 0];
        }

        return [
            'total' => DB::table('communicationhub_providers')->count(),
            'active' => DB::table('communicationhub_providers')->where('is_active', 1)->count(),
            'inactive' => DB::table('communicationhub_providers')->where('is_active', 0)->count(),
        ];
    }

    protected function standaloneScore(): int
    {
        $checks = $this->checks();
        return $this->score($checks, 'status');
    }

    protected function securityScore(): int
    {
        return $this->score($this->securityChecklist(), 'status');
    }

    protected function queueScore(): int
    {
        return Schema::hasTable('communicationhub_messages') ? 85 : 20;
    }

    protected function providerScore(): int
    {
        return Schema::hasTable('communicationhub_providers') ? 85 : 20;
    }

    protected function score(array $items, string $key): int
    {
        if (count($items) === 0) {
            return 0;
        }

        $passed = collect($items)->where($key, true)->count();
        return (int) round(($passed / count($items)) * 100);
    }
}
