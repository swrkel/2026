<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class LeadsNewReleaseReadinessService
{
    public function run(): array
    {
        return [
            'module_root' => $this->moduleRoot(),
            'standalone_audit' => $this->standaloneAudit(),
            'route_audit' => $this->routeAudit(),
            'file_checklist' => $this->fileChecklist(),
            'recommended_indexes' => $this->recommendedIndexes(),
        ];
    }

    public function standaloneAudit(): array
    {
        $root = $this->moduleRoot();
        $blocked = [
            'Modules\\Leads\\',
            'Modules/Leads/',
            'leads::',
            'leads.',
        ];

        $hits = [];
        if (! File::isDirectory($root)) {
            return ['status' => 'failed', 'message' => 'Modules/LeadsNew not found', 'hits' => []];
        }

        foreach (File::allFiles($root) as $file) {
            $path = $file->getPathname();
            if (! preg_match('/\.(php|blade\.php|js|css|json|yml|yaml|txt)$/i', $path)) {
                continue;
            }
            $contents = File::get($path);
            foreach ($blocked as $needle) {
                if (Str::contains($contents, $needle)) {
                    $hits[] = ['file' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path), 'match' => $needle];
                }
            }
        }

        return [
            'status' => empty($hits) ? 'passed' : 'review_required',
            'message' => empty($hits) ? 'No direct dependency on old Leads module found.' : 'Review listed references before production deployment.',
            'hits' => $hits,
        ];
    }

    public function routeAudit(): array
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => implode('|', $route->methods()),
                'action' => $route->getActionName(),
            ];
        })->filter(function ($route) {
            return Str::startsWith((string) $route['uri'], 'leads-new') || Str::startsWith((string) $route['name'], 'leads-new.');
        })->values()->all();

        return [
            'status' => count($routes) > 0 ? 'passed' : 'review_required',
            'count' => count($routes),
            'routes' => $routes,
        ];
    }

    public function fileChecklist(): array
    {
        $items = [
            'module_json' => 'module.json',
            'routes' => 'Routes/web.php',
            'api_routes' => 'Routes/api.php',
            'service_provider' => 'Providers/LeadsNewServiceProvider.php',
            'controllers' => 'Http/Controllers',
            'models' => 'Models',
            'views' => 'Resources/views',
            'assets' => 'Resources/assets',
            'migrations' => 'Database/Migrations',
            'seeders' => 'Database/Seeders',
            'permissions' => 'Database/Seeders/LeadsNewPermissionSeeder.php',
            'reports' => 'Reports',
            'docs' => 'Docs',
        ];

        $root = $this->moduleRoot();
        $result = [];
        foreach ($items as $key => $relative) {
            $result[$key] = File::exists($root . DIRECTORY_SEPARATOR . $relative);
        }
        return $result;
    }

    public function recommendedIndexes(): array
    {
        return [
            'leads_new_leads' => ['business_id,status_id,assigned_to', 'business_id,created_at', 'business_id,next_followup_date'],
            'leads_new_followups' => ['business_id,followup_date,status', 'lead_id,followup_date'],
            'leads_new_activities' => ['business_id,lead_id,created_at'],
            'leads_new_opportunities' => ['business_id,stage,status', 'business_id,expected_close_date'],
            'leads_new_documents' => ['business_id,lead_id,document_type'],
        ];
    }

    protected function moduleRoot(): string
    {
        return base_path('Modules/LeadsNew');
    }
}
