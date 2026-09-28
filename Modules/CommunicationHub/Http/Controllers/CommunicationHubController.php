<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubController extends Controller
{
    public function dashboard()
    {
        $setupRequired = ! TenantConnection::hasTable('communication_hub_messages');

        $stats = [
            'messages_today' => 0,
            'sent_today' => 0,
            'failed_today' => 0,
            'pending' => 0,
            'providers' => 0,
            'templates' => 0,
            'cost_today' => 0,
        ];

        if (! $setupRequired) {
            try {
                $messages = $this->businessScoped(TenantConnection::db()->table('communication_hub_messages'), 'communication_hub_messages');
                $stats['messages_today'] = (clone $messages)->whereDate('created_at', today())->count();
                $stats['sent_today'] = (clone $messages)->whereDate('created_at', today())->whereIn('status', ['sent', 'delivered'])->count();
                $stats['failed_today'] = (clone $messages)->whereDate('created_at', today())->where('status', 'failed')->count();
                $stats['pending'] = (clone $messages)->whereIn('status', ['pending', 'queued', 'scheduled'])->count();

                $costColumn = TenantConnection::hasColumn('communication_hub_messages', 'actual_cost')
                    ? 'actual_cost'
                    : (TenantConnection::hasColumn('communication_hub_messages', 'cost') ? 'cost' : null);

                if ($costColumn) {
                    $stats['cost_today'] = (clone $messages)->whereDate('created_at', today())->sum($costColumn);
                }
            } catch (\Throwable $e) {
                $setupRequired = true;
            }
        }

        try {
            if (TenantConnection::hasTable('communication_hub_providers')) {
                $stats['providers'] = $this->businessScoped(TenantConnection::db()->table('communication_hub_providers'), 'communication_hub_providers')->count();
            }
        } catch (\Throwable $e) {}

        try {
            if (TenantConnection::hasTable('communication_hub_templates')) {
                $stats['templates'] = $this->businessScoped(TenantConnection::db()->table('communication_hub_templates'), 'communication_hub_templates')->count();
            }
        } catch (\Throwable $e) {}

        return view('communicationhub::dashboard', compact('stats', 'setupRequired'));
    }

    protected function businessScoped($query, string $table)
    {
        $businessId = session('business.id') ?? session('business_id') ?? optional(auth()->user())->business_id;

        if ($businessId && TenantConnection::hasColumn($table, 'business_id')) {
            $query->where(function ($q) use ($businessId) {
                $q->where('business_id', $businessId)->orWhereNull('business_id');
            });
        }

        return $query;
    }
}
