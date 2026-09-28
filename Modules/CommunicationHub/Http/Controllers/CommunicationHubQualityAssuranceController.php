<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Modules\CommunicationHub\Services\Support\BusinessContext;

class CommunicationHubQualityAssuranceController extends Controller
{
    public function index(BusinessContext $context)
    {
        $businessId = $context->businessId();
        $locationId = $context->locationId();

        $requiredTables = [
            'communication_hub_messages',
            'communication_hub_templates',
            'communication_hub_providers',
            'communication_hub_otp_requests',
            'communication_hub_campaigns',
            'communication_hub_automation_rules',
            'communication_hub_workflow_events',
            'communication_hub_workflow_rules',
            'communication_hub_in_app_notifications',
            'communication_hub_live_chat_conversations',
            'communication_hub_internal_messages',
            'communication_hub_api_clients',
            'communication_hub_audit_logs',
        ];

        $tableChecks = collect($requiredTables)->map(function ($table) use ($businessId) {
            $exists = Schema::hasTable($table);
            $hasBusiness = $exists && Schema::hasColumn($table, 'business_id');
            return [
                'table' => $table,
                'exists' => $exists,
                'has_business_id' => $hasBusiness,
                'rows' => $exists ? $this->safeCount($table) : null,
                'business_rows' => ($exists && $hasBusiness && $businessId) ? $this->safeCount($table, ['business_id' => $businessId]) : null,
                'status' => $exists && $hasBusiness ? 'pass' : ($exists ? 'warning' : 'fail'),
            ];
        })->values()->all();

        $routeNames = [
            'communicationhub.dashboard',
            'communicationhub.commercial.sms_dashboard',
            'communicationhub.otp.index',
            'communicationhub.commercial.whatsapp_dashboard',
            'communicationhub.commercial.push_dashboard',
            'communicationhub.commercial.in_app_dashboard',
            'communicationhub.commercial.chat_dashboard',
            'communicationhub.commercial.automation_dashboard',
            'communicationhub.commercial.workflow_dashboard',
            'communicationhub.commercial.analytics_dashboard',
            'communicationhub.excellence.executive_centre',
            'communicationhub.quality.index',
        ];

        $routeChecks = collect($routeNames)->map(function ($name) {
            return [
                'name' => $name,
                'exists' => Route::has($name),
                'url' => Route::has($name) ? route($name, [], false) : null,
            ];
        })->values()->all();

        $queueSummary = $this->queueSummary($businessId);
        $errorSummary = $this->errorSummary($businessId);
        $recentAudits = $this->recentRows('communication_hub_audit_logs', $businessId, 10);

        $scoreItems = collect($tableChecks)->where('status', 'pass')->count() + collect($routeChecks)->where('exists', true)->count();
        $scoreTotal = count($tableChecks) + count($routeChecks);
        $readinessScore = $scoreTotal > 0 ? round(($scoreItems / $scoreTotal) * 100, 2) : 0;

        return view('communicationhub::quality.index', compact(
            'businessId', 'locationId', 'tableChecks', 'routeChecks', 'queueSummary', 'errorSummary', 'recentAudits', 'readinessScore'
        ))->with('checkedAt', Carbon::now())->with('database', DB::connection()->getDatabaseName());
    }

    private function safeCount(string $table, array $where = []): int
    {
        try {
            $query = DB::table($table);
            foreach ($where as $column => $value) {
                $query->where($column, $value);
            }
            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function queueSummary($businessId): array
    {
        if (!Schema::hasTable('communication_hub_messages')) {
            return [];
        }
        try {
            $query = DB::table('communication_hub_messages')->selectRaw('COALESCE(channel, "unknown") as channel, COALESCE(status, "unknown") as status, COUNT(*) as total')->groupBy('channel', 'status');
            if ($businessId && Schema::hasColumn('communication_hub_messages', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            return $query->orderBy('channel')->orderBy('status')->get()->map(fn($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function errorSummary($businessId): array
    {
        if (!Schema::hasTable('communication_hub_messages')) {
            return [];
        }
        try {
            $query = DB::table('communication_hub_messages')->whereIn('status', ['failed', 'error', 'bounced', 'cancelled']);
            if ($businessId && Schema::hasColumn('communication_hub_messages', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            return $query->latest('id')->limit(15)->get()->map(fn($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function recentRows(string $table, $businessId, int $limit): array
    {
        if (!Schema::hasTable($table)) {
            return [];
        }
        try {
            $query = DB::table($table);
            if ($businessId && Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            return $query->latest('id')->limit($limit)->get()->map(fn($r) => (array) $r)->all();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
