<?php
namespace Modules\AirlineTicketingNew\Services\Diagnostics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleDiagnosticsService
{
    public function run(int $businessId): array
    {
        return [
            'database' => DB::connection()->getDatabaseName(),
            'tables' => $this->tableStatus(),
            'business_record_counts' => $this->counts($businessId),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'checked_at' => now()->toDateTimeString(),
        ];
    }

    private function tableStatus(): array
    {
        $tables = [
            'atn_settings','atn_airlines','atn_airports','atn_passengers',
            'atn_reservations','atn_tickets','atn_invoices','atn_payments',
            'atn_refunds','atn_operational_tasks','atn_journal_entries',
        ];

        return collect($tables)->mapWithKeys(fn ($table) => [
            $table => Schema::hasTable($table),
        ])->all();
    }

    private function counts(int $businessId): array
    {
        return [
            'reservations' => DB::table('atn_reservations')->where('business_id', $businessId)->count(),
            'tickets' => DB::table('atn_tickets')->where('business_id', $businessId)->count(),
            'invoices' => DB::table('atn_invoices')->where('business_id', $businessId)->count(),
            'payments' => DB::table('atn_payments')->where('business_id', $businessId)->count(),
        ];
    }
}
