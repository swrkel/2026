<?php

namespace Modules\BankingUI\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class BankingTestManagerService
{
    public function businessId()
    {
        if (function_exists('request') && request()->session()->has('user.business_id')) {
            return request()->session()->get('user.business_id');
        }
        return optional(auth()->user())->business_id;
    }

    public function modules()
    {
        return config('bankingui.test_manager.modules', []);
    }

    public function ensureDefaultStatuses()
    {
        if (!DB::getSchemaBuilder()->hasTable('banking_test_module_statuses')) {
            return;
        }

        $businessId = $this->businessId();
        foreach ($this->modules() as $key => $name) {
            $exists = DB::table('banking_test_module_statuses')
                ->where('business_id', $businessId)
                ->where('module_key', $key)
                ->exists();

            if (!$exists) {
                DB::table('banking_test_module_statuses')->insert([
                    'business_id' => $businessId,
                    'module_key' => $key,
                    'module_name' => $name,
                    'status' => in_array($key, ['treasury_payments']) ? 'Under Development' : 'Ready for UI Testing',
                    'development_percent' => in_array($key, ['treasury_payments']) ? 10 : 70,
                    'ui_tested_percent' => 0,
                    'uat_percent' => 0,
                    'production_ready_percent' => 0,
                    'created_by' => optional(auth()->user())->id,
                    'updated_by' => optional(auth()->user())->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function dashboard()
    {
        $this->ensureDefaultStatuses();
        $businessId = $this->businessId();
        $statuses = collect();
        $issues = collect();

        if (DB::getSchemaBuilder()->hasTable('banking_test_module_statuses')) {
            $statuses = DB::table('banking_test_module_statuses')
                ->where('business_id', $businessId)
                ->orderBy('module_name')
                ->get();
        }
        if (DB::getSchemaBuilder()->hasTable('banking_test_issues')) {
            $issues = DB::table('banking_test_issues')
                ->where('business_id', $businessId)
                ->latest('id')
                ->get();
        }

        return [
            'statuses' => $statuses,
            'issues' => $issues,
            'openIssues' => $issues->whereNotIn('status', ['Closed', 'Resolved'])->count(),
            'criticalIssues' => $issues->where('priority', 'Critical')->whereNotIn('status', ['Closed', 'Resolved'])->count(),
            'modulesReady' => $statuses->whereIn('status', ['Ready for UI Testing', 'UI Testing', 'UAT', 'Ready for Production', 'Production'])->count(),
            'overallUi' => (int) round($statuses->avg('ui_tested_percent') ?: 0),
        ];
    }

    public function coverageRows()
    {
        $this->ensureDefaultStatuses();
        if (!DB::getSchemaBuilder()->hasTable('banking_test_module_statuses')) {
            return collect();
        }
        return DB::table('banking_test_module_statuses')
            ->where('business_id', $this->businessId())
            ->orderBy('module_name')
            ->get();
    }

    public function routeHealthRows()
    {
        $rows = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            $uri = $route->uri();
            if (!str_contains($uri, 'banking') && !str_contains((string) $name, 'banking')) {
                continue;
            }
            $action = $route->getActionName();
            $hasController = !str_contains($action, 'Closure');
            $rows[] = [
                'method' => implode('|', $route->methods()),
                'uri' => $uri,
                'name' => $name ?: '-',
                'action' => $action,
                'status' => $hasController ? 'OK' : 'Check',
            ];
        }
        return collect($rows)->sortBy('uri')->values();
    }

    public function prioritySummary()
    {
        if (!DB::getSchemaBuilder()->hasTable('banking_test_issues')) {
            return collect();
        }
        return DB::table('banking_test_issues')
            ->select('priority', DB::raw('count(*) as total'))
            ->where('business_id', $this->businessId())
            ->whereNotIn('status', ['Closed', 'Resolved'])
            ->groupBy('priority')
            ->pluck('total', 'priority');
    }
}
