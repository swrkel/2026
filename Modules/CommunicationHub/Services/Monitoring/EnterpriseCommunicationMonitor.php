<?php

namespace Modules\CommunicationHub\Services\Monitoring;

use Illuminate\Support\Facades\Schema;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;
use Modules\CommunicationHub\Entities\CommunicationHubOtp;
use Modules\CommunicationHub\Entities\CommunicationHubProvider;
use Modules\CommunicationHub\Entities\CommunicationHubTemplate;

class EnterpriseCommunicationMonitor
{
    public function dashboardStats(): array
    {
        return [
            'sms_today' => $this->messageCount('sms'),
            'email_today' => $this->messageCount('email'),
            'whatsapp_today' => $this->messageCount('whatsapp'),
            'push_today' => $this->messageCount('push'),
            'otp_generated' => class_exists(CommunicationHubOtp::class) ? CommunicationHubOtp::whereDate('created_at', today())->count() : 0,
            'otp_verified' => class_exists(CommunicationHubOtp::class) ? CommunicationHubOtp::whereDate('created_at', today())->whereNotNull('verified_at')->count() : 0,
            'queue_size' => CommunicationHubMessage::whereIn('status', ['pending', 'scheduled', 'retrying'])->count(),
            'failed_messages' => CommunicationHubMessage::whereDate('created_at', today())->where('status', 'failed')->count(),
            'estimated_cost_today' => $this->sumMessageColumn('estimated_cost', 'cost'),
            'actual_cost_today' => $this->sumMessageColumn('actual_cost', 'cost'),
            'templates' => CommunicationHubTemplate::count(),
            'providers' => CommunicationHubProvider::count(),
        ];
    }

    public function providerHealth(): array
    {
        return CommunicationHubProvider::orderBy('channel')->orderBy('name')->get()->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->name,
                'channel' => $provider->channel,
                'status' => $provider->status ?? ($provider->is_active ? 'online' : 'offline'),
                'is_active' => (bool) ($provider->is_active ?? false),
                'priority' => $provider->priority ?? 0,
                'last_success_at' => $provider->last_success_at ?? null,
                'last_failure_at' => $provider->last_failure_at ?? null,
                'response_time_ms' => $provider->average_response_ms ?? ($provider->response_time_ms ?? null),
                'daily_usage' => CommunicationHubMessage::whereDate('created_at', today())->where('provider_id', $provider->id)->count(),
                'monthly_usage' => CommunicationHubMessage::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->where('provider_id', $provider->id)->count(),
                'last_error' => $provider->last_response_message ?? ($provider->last_error ?? null),
            ];
        })->toArray();
    }

    public function queueStats(): array
    {
        $statuses = ['pending', 'scheduled', 'processing', 'retrying', 'sent', 'delivered', 'failed', 'cancelled'];
        $stats = [];
        foreach ($statuses as $status) {
            $stats[$status] = CommunicationHubMessage::where('status', $status)->count();
        }
        return $stats;
    }

    public function recentQueueMessages()
    {
        return CommunicationHubMessage::latest()->limit(50)->get();
    }

    public function channelStats(): array
    {
        $channels = ['sms', 'email', 'whatsapp', 'push'];
        $stats = [];
        foreach ($channels as $channel) {
            $stats[$channel] = [
                'today' => $this->messageCount($channel),
                'failed' => CommunicationHubMessage::whereDate('created_at', today())->where('channel', $channel)->where('status', 'failed')->count(),
                'sent' => CommunicationHubMessage::whereDate('created_at', today())->where('channel', $channel)->whereIn('status', ['sent', 'delivered'])->count(),
            ];
        }
        return $stats;
    }

    public function costStats(): array
    {
        return [
            'estimated_today' => $this->sumMessageColumn('estimated_cost', 'cost'),
            'actual_today' => $this->sumMessageColumn('actual_cost', 'cost'),
            'wallet_failed_today' => $this->countMessageColumnValue('wallet_charge_status', 'failed'),
            'wallet_approved_today' => $this->countMessageColumnValue('wallet_charge_status', 'approved'),
        ];
    }

    public function otpStats(): array
    {
        if (!class_exists(CommunicationHubOtp::class)) {
            return ['generated' => 0, 'verified' => 0, 'expired' => 0, 'failed' => 0];
        }

        return [
            'generated' => CommunicationHubOtp::whereDate('created_at', today())->count(),
            'verified' => CommunicationHubOtp::whereDate('created_at', today())->whereNotNull('verified_at')->count(),
            'expired' => CommunicationHubOtp::whereDate('created_at', today())->where('expires_at', '<', now())->whereNull('verified_at')->count(),
            'failed' => CommunicationHubOtp::whereDate('created_at', today())->where('attempts', '>', 0)->whereNull('verified_at')->count(),
        ];
    }

    public function communicationAuditSummary(): array
    {
        return [
            'messages_today' => CommunicationHubMessage::whereDate('created_at', today())->count(),
            'delivery_events_available' => Schema::hasTable('communication_hub_delivery_events'),
            'audit_logs_available' => Schema::hasTable('communication_hub_audit_logs'),
            'wallet_tracking_available' => Schema::hasColumn('communication_hub_messages', 'wallet_transaction_reference'),
            'provider_tracking_available' => Schema::hasColumn('communication_hub_messages', 'provider_id'),
        ];
    }

    protected function messageCount(string $channel): int
    {
        return CommunicationHubMessage::whereDate('created_at', today())->where('channel', $channel)->count();
    }

    protected function sumMessageColumn(string $preferredColumn, string $fallbackColumn = 'cost'): float
    {
        $column = Schema::hasColumn('communication_hub_messages', $preferredColumn) ? $preferredColumn : $fallbackColumn;
        return (float) CommunicationHubMessage::whereDate('created_at', today())->sum($column);
    }

    protected function countMessageColumnValue(string $column, string $value): int
    {
        if (!Schema::hasColumn('communication_hub_messages', $column)) {
            return 0;
        }

        return CommunicationHubMessage::whereDate('created_at', today())->where($column, $value)->count();
    }
}
