<?php
namespace Modules\AirlineTicketingNew\Services\Deployment;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeploymentReadinessService
{
    public function check(): array
    {
        $requiredTables = [
            'atn_settings','atn_passengers','atn_reservations','atn_tickets',
            'atn_invoices','atn_payments','atn_refunds','atn_operational_tasks',
        ];

        $missing = collect($requiredTables)
            ->reject(fn ($table) => Schema::hasTable($table))
            ->values()
            ->all();

        return [
            'database' => DB::connection()->getDatabaseName(),
            'missing_tables' => $missing,
            'storage_writable' => is_writable(storage_path()),
            'cache_path_writable' => is_writable(storage_path('framework/cache')),
            'status' => empty($missing) ? 'ready' : 'blocked',
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    public function clearCaches(): array
    {
        Artisan::call('optimize:clear');

        return [
            'status' => 'completed',
            'output' => Artisan::output(),
        ];
    }
}
