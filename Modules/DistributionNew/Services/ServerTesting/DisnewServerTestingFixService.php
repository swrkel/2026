<?php

namespace Modules\DistributionNew\Services\ServerTesting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DisnewServerTestingFixService
{
    public function run(int $businessId, ?int $locationId = null): array
    {
        return [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'database' => DB::connection()->getDatabaseName(),
            'tables' => $this->tables(),
            'routes' => $this->routes(),
            'assets' => $this->assets(),
            'summary' => $this->summary(),
        ];
    }

    protected function tables(): array
    {
        $tables = [
            'disnew_sales_orders',
            'disnew_sales_order_lines',
            'disnew_sales_invoices',
            'disnew_loading_plans',
            'disnew_vehicle_stocks',
            'disnew_warehouses',
            'disnew_returns',
            'disnew_credit_notes',
            'disnew_server_testing_checks',
            'disnew_server_fix_pack_logs',
        ];

        return collect($tables)->map(fn ($table) => [
            'table' => $table,
            'exists' => Schema::hasTable($table),
        ])->values()->all();
    }

    protected function routes(): array
    {
        $expected = [
            'distribution-new.dashboard',
            'distribution-new.sales-orders.index',
            'distribution-new.sales-invoices.index',
            'distribution-new.loading.index',
            'distribution-new.vehicles.index',
            'distribution-new.server-testing.fix-pack-2',
        ];

        $registered = collect(Route::getRoutes())->map(fn ($route) => $route->getName())->filter()->values();

        return collect($expected)->map(fn ($name) => [
            'route' => $name,
            'registered' => $registered->contains($name),
        ])->values()->all();
    }

    protected function assets(): array
    {
        $paths = [
            'Modules/DistributionNew/Resources/js/disnew_stage27_server_testing.js',
            'Modules/DistributionNew/Resources/css/disnew_stage27_server_testing.css',
        ];

        return collect($paths)->map(fn ($path) => [
            'path' => $path,
            'exists' => file_exists(base_path($path)),
        ])->values()->all();
    }

    protected function summary(): array
    {
        return [
            'status' => 'ready_for_server_validation',
            'message' => 'Use this page after replacing the DistributionNew module and running the DISNEW SQL/migrations in each tenant database.',
        ];
    }
}
