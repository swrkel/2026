<?php

namespace Modules\AirlineTicketingNew\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleHealthCheckService
{
    public function run(): array
    {
        $requiredTables = [
            'atn_settings','atn_airlines','atn_airports','atn_passengers','atn_quotations',
            'atn_reservations','atn_tickets','atn_invoices','atn_payments','atn_refunds',
            'atn_supplier_settlements','atn_operational_tasks'
        ];

        $missing = collect($requiredTables)->reject(fn ($table) => Schema::hasTable($table))->values()->all();

        return [
            'database_connection' => DB::connection()->getDatabaseName(),
            'missing_tables' => $missing,
            'status' => empty($missing) ? 'healthy' : 'attention_required',
            'checked_at' => now()->toDateTimeString(),
        ];
    }
}
