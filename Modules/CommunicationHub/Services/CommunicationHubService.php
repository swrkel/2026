<?php

namespace Modules\CommunicationHub\Services;

use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Entities\CommunicationHubProvider;
use Modules\CommunicationHub\Entities\CommunicationHubTemplate;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubService
{
    public function dashboardSummary(): array
    {
        try {
            if (! TenantConnection::hasTable('communication_hub_messages')) {
                return $this->emptySummary();
            }

            return [
                'providers' => TenantConnection::hasTable('communication_hub_providers') ? CommunicationHubProvider::count() : 0,
                'active_providers' => TenantConnection::hasTable('communication_hub_providers') ? CommunicationHubProvider::where('is_active', 1)->count() : 0,
                'templates' => TenantConnection::hasTable('communication_hub_templates') ? CommunicationHubTemplate::count() : 0,
                'queued' => CommunicationHubMessage::where('status', 'pending')->count(),
                'sent_today' => CommunicationHubMessage::where('status', 'sent')->whereDate('sent_at', now()->toDateString())->count(),
                'failed_today' => CommunicationHubMessage::where('status', 'failed')->whereDate('created_at', now()->toDateString())->count(),
                'monthly_cost' => TenantConnection::hasColumn('communication_hub_messages', 'cost')
                    ? CommunicationHubMessage::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('cost')
                    : 0,
            ];
        } catch (\Throwable $e) {
            return $this->emptySummary();
        }
    }

    public function queueMessage(array $data): ?CommunicationHubMessage
    {
        if (! TenantConnection::hasTable('communication_hub_messages')) {
            return null;
        }

        return CommunicationHubMessage::create([
            'business_id' => $data['business_id'] ?? session('business.id') ?? session('business_id'),
            'channel' => $data['channel'],
            'recipient' => $data['recipient'],
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'payload' => $data['payload'] ?? [],
            'status' => 'pending',
            'priority' => $data['priority'] ?? 'normal',
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'created_by' => auth()->id(),
        ]);
    }

    public function renderTemplate(CommunicationHubTemplate $template, array $data): string
    {
        $content = (string) $template->content;
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', (string) $value, $content);
        }
        return $content;
    }

    public function recentMessages(int $limit = 20)
    {
        try {
            if (! TenantConnection::hasTable('communication_hub_messages')) {
                return collect();
            }
            return CommunicationHubMessage::latest()->limit($limit)->get();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function emptySummary(): array
    {
        return [
            'providers' => 0,
            'active_providers' => 0,
            'templates' => 0,
            'queued' => 0,
            'sent_today' => 0,
            'failed_today' => 0,
            'monthly_cost' => 0,
        ];
    }
}
