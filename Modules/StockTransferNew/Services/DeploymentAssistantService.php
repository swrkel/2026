<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\StockTransferNew\Entities\DeploymentSqlExecution;
use Modules\StockTransferNew\Entities\DeploymentCheckResult;

class DeploymentAssistantService
{
    public function dashboard(Request $request): array
    {
        $businessId = $this->businessId();
        $checks = $this->buildChecks($businessId);
        $summary = [
            'total' => count($checks),
            'passed' => collect($checks)->where('status', 'passed')->count(),
            'warning' => collect($checks)->where('status', 'warning')->count(),
            'failed' => collect($checks)->where('status', 'failed')->count(),
        ];

        $this->persistChecks($checks, $businessId);

        return compact('checks', 'summary');
    }

    public function sqlTracker(Request $request): array
    {
        $executions = DeploymentSqlExecution::query()
            ->where('business_id', $this->businessId())
            ->orderByDesc('executed_at')
            ->limit(100)
            ->get();

        $requiredScripts = $this->requiredSqlScripts();

        return compact('executions', 'requiredScripts');
    }

    public function markSqlExecuted(Request $request): DeploymentSqlExecution
    {
        return DeploymentSqlExecution::updateOrCreate([
            'business_id' => $this->businessId(),
            'script_name' => $request->script_name,
            'database_scope' => $request->database_scope,
        ], [
            'executed_by' => auth()->id(),
            'executed_at' => now(),
            'remarks' => $request->remarks,
        ]);
    }

    public function rollbackPlan(Request $request): array
    {
        $steps = [
            ['step' => 1, 'title' => 'Take database backup', 'details' => 'Backup tenant database before any SQL execution.'],
            ['step' => 2, 'title' => 'Deploy files', 'details' => 'Upload only StockTransferNew module files and public assets.'],
            ['step' => 3, 'title' => 'Run SQL scripts', 'details' => 'Run CREATE first, ALTER second, INSERT third. Avoid duplicate manual inserts.'],
            ['step' => 4, 'title' => 'Verify permissions', 'details' => 'Refresh module permissions and assign to testing roles.'],
            ['step' => 5, 'title' => 'Rollback if needed', 'details' => 'Restore backup and remove only StockTransferNew changed files from this parcel.'],
        ];

        $executions = DeploymentSqlExecution::query()
            ->where('business_id', $this->businessId())
            ->orderBy('script_name')
            ->get();

        return compact('steps', 'executions');
    }

    public function exportChecks(Request $request): StreamedResponse
    {
        $checks = $this->buildChecks($this->businessId());
        return response()->streamDownload(function () use ($checks) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Check', 'Status', 'Message']);
            foreach ($checks as $check) {
                fputcsv($out, [$check['name'], $check['status'], $check['message']]);
            }
            fclose($out);
        }, 'stock_transfer_new_deployment_checks.csv');
    }

    protected function buildChecks($businessId): array
    {
        return [
            $this->tableCheck('stn_transfers'),
            $this->tableCheck('stn_transfer_lines'),
            $this->tableCheck('stn_stock_movements'),
            $this->tableCheck('stn_approval_matrices'),
            $this->permissionCheck('stock_transfer_new.view'),
            $this->permissionCheck('stock_transfer_new.create'),
            $this->permissionCheck('stock_transfer_new.dispatch'),
            $this->permissionCheck('stock_transfer_new.receive'),
            $this->assetCheck('public/modules/stocktransfernew/css/stn_040.css'),
            $this->assetCheck('public/modules/stocktransfernew/js/stn_040.js'),
            [
                'name' => 'Tenant business scope',
                'status' => $businessId ? 'passed' : 'warning',
                'message' => $businessId ? 'Business context resolved.' : 'Business context not resolved; verify tenant login.',
            ],
        ];
    }

    protected function tableCheck(string $table): array
    {
        try {
            $exists = DB::getSchemaBuilder()->hasTable($table);
            return ['name' => 'Table: '.$table, 'status' => $exists ? 'passed' : 'failed', 'message' => $exists ? 'Available' : 'Missing'];
        } catch (\Throwable $e) {
            return ['name' => 'Table: '.$table, 'status' => 'warning', 'message' => $e->getMessage()];
        }
    }

    protected function permissionCheck(string $permission): array
    {
        try {
            $exists = DB::table('permissions')->where('name', $permission)->exists();
            return ['name' => 'Permission: '.$permission, 'status' => $exists ? 'passed' : 'warning', 'message' => $exists ? 'Available' : 'Permission not found'];
        } catch (\Throwable $e) {
            return ['name' => 'Permission: '.$permission, 'status' => 'warning', 'message' => 'Permission table not readable'];
        }
    }

    protected function assetCheck(string $path): array
    {
        $exists = file_exists(base_path($path));
        return ['name' => 'Asset: '.basename($path), 'status' => $exists ? 'passed' : 'warning', 'message' => $exists ? 'Available' : 'Not found on server'];
    }

    protected function persistChecks(array $checks, $businessId): void
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_deployment_check_results')) {
            return;
        }

        foreach ($checks as $check) {
            DeploymentCheckResult::create([
                'business_id' => $businessId,
                'check_name' => $check['name'],
                'status' => $check['status'],
                'message' => $check['message'],
                'checked_by' => auth()->id(),
                'checked_at' => now(),
            ]);
        }
    }

    protected function requiredSqlScripts(): array
    {
        return [
            'STN_001_CREATE_TABLES.sql',
            'STN_001_INSERT_PERMISSIONS.sql',
            'STN_040_MASTER_SQL.sql',
            'STN_041_CREATE_TABLES.sql',
            'STN_041_INSERT_PERMISSIONS.sql',
        ];
    }

    protected function businessId()
    {
        return session('business.id') ?? optional(auth()->user())->business_id;
    }
}
