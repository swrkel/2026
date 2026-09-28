<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CommunicationHubOperationsController extends Controller
{
    public function index()
    {
        $tables = [
            'communication_messages',
            'communication_templates',
            'communication_providers',
            'communication_campaigns',
            'communication_otp_logs',
            'communication_api_tokens',
            'communication_audit_logs',
            'communication_provider_health',
            'communication_event_registry',
            'communication_cost_entries',
        ];

        $tableStatus = collect($tables)->map(function ($table) {
            return [
                'table' => $table,
                'exists' => Schema::hasTable($table),
            ];
        });

        $queueSummary = [];
        if (Schema::hasTable('communication_messages')) {
            $queueSummary = DB::table('communication_messages')
                ->select('channel', 'status', DB::raw('COUNT(*) as total'))
                ->groupBy('channel', 'status')
                ->orderBy('channel')
                ->orderBy('status')
                ->get();
        }

        $providerSummary = [];
        if (Schema::hasTable('communication_providers')) {
            $providerSummary = DB::table('communication_providers')
                ->select('type', 'status', DB::raw('COUNT(*) as total'))
                ->groupBy('type', 'status')
                ->orderBy('type')
                ->get();
        }

        return view('communicationhub::operations.support', compact('tableStatus', 'queueSummary', 'providerSummary'));
    }
}
