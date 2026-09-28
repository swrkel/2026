<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunicationHubDiagnosticsController extends Controller
{
    public function index(Request $request)
    {
        $businessId = session('business.id') ?? $request->get('business_id');
        $locationId = session('business_location_id') ?? $request->get('business_location_id');

        $tables = [
            'communication_hub_messages',
            'communication_hub_delivery_events',
            'communication_hub_otps',
            'communication_hub_providers',
            'communication_hub_templates',
            'communication_hub_campaigns',
            'communication_hub_api_clients',
            'communication_hub_api_request_logs',
            'communication_hub_sms_wallets',
            'communication_hub_automation_events',
            'communication_hub_workflow_event_logs',
        ];

        $tableStatus = [];
        foreach ($tables as $table) {
            $tableStatus[] = [
                'table' => $table,
                'exists' => $this->tableExists($table),
                'rows' => $this->safeCount($table),
                'business_rows' => $businessId ? $this->safeCount($table, ['business_id' => $businessId]) : null,
            ];
        }

        $messageStats = $this->messageStats($businessId, $locationId);
        $otpStats = $this->statusStats('communication_hub_otps', $businessId, $locationId);
        $providerStats = $this->statusStats('communication_hub_providers', $businessId, $locationId);
        $failedMessages = $this->recentRows('communication_hub_messages', $businessId, $locationId, ['failed', 'error']);
        $pendingMessages = $this->recentRows('communication_hub_messages', $businessId, $locationId, ['pending', 'queued', 'scheduled']);

        return view('communicationhub::diagnostics.index', [
            'checkedAt' => now(),
            'database' => DB::getDatabaseName(),
            'businessId' => $businessId,
            'locationId' => $locationId,
            'tableStatus' => $tableStatus,
            'messageStats' => $messageStats,
            'otpStats' => $otpStats,
            'providerStats' => $providerStats,
            'failedMessages' => $failedMessages,
            'pendingMessages' => $pendingMessages,
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            $rows = DB::select('SELECT COUNT(*) AS aggregate FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', [$table]);
            return (int)($rows[0]->aggregate ?? 0) > 0;
        } catch (\Throwable $e) {
            Log::warning('Communication Hub table check failed', ['table' => $table, 'error' => $e->getMessage()]);
            return false;
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        try {
            $rows = DB::select('SELECT COUNT(*) AS aggregate FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
            return (int)($rows[0]->aggregate ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function safeCount(string $table, array $where = []): ?int
    {
        if (!$this->tableExists($table)) {
            return null;
        }

        try {
            $query = DB::table($table);
            foreach ($where as $column => $value) {
                if ($value !== null && $this->hasColumn($table, $column)) {
                    $query->where($column, $value);
                }
            }
            return (int) $query->count();
        } catch (\Throwable $e) {
            Log::warning('Communication Hub count failed', ['table' => $table, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function messageStats($businessId, $locationId): array
    {
        if (!$this->tableExists('communication_hub_messages')) {
            return [];
        }

        try {
            $query = DB::table('communication_hub_messages');
            if ($businessId && $this->hasColumn('communication_hub_messages', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($locationId && $this->hasColumn('communication_hub_messages', 'business_location_id')) {
                $query->where('business_location_id', $locationId);
            }
            return $query->selectRaw('COALESCE(channel, "unknown") AS channel, COALESCE(status, "unknown") AS status, COUNT(*) AS total')
                ->groupBy('channel', 'status')
                ->orderBy('channel')
                ->orderBy('status')
                ->get()
                ->map(fn($row) => (array) $row)
                ->toArray();
        } catch (\Throwable $e) {
            Log::warning('Communication Hub message diagnostic failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function statusStats(string $table, $businessId, $locationId): array
    {
        if (!$this->tableExists($table) || !$this->hasColumn($table, 'status')) {
            return [];
        }

        try {
            $query = DB::table($table);
            if ($businessId && $this->hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($locationId && $this->hasColumn($table, 'business_location_id')) {
                $query->where('business_location_id', $locationId);
            }
            return $query->selectRaw('COALESCE(status, "unknown") AS status, COUNT(*) AS total')
                ->groupBy('status')
                ->orderBy('status')
                ->get()
                ->map(fn($row) => (array) $row)
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function recentRows(string $table, $businessId, $locationId, array $statuses): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }

        try {
            $query = DB::table($table);
            if ($businessId && $this->hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if ($locationId && $this->hasColumn($table, 'business_location_id')) {
                $query->where('business_location_id', $locationId);
            }
            if ($this->hasColumn($table, 'status')) {
                $query->whereIn('status', $statuses);
            }
            $orderColumn = $this->hasColumn($table, 'created_at') ? 'created_at' : 'id';
            return $query->orderByDesc($orderColumn)->limit(15)->get()->map(fn($row) => (array) $row)->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
