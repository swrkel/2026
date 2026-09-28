<?php

namespace Modules\PriceChangeNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsurePriceChangeNewSchema
{
    /** @var array<string, bool> */
    private static array $readyByDatabase = [];

    public function handle(Request $request, Closure $next)
    {
        $databaseName = (string) DB::connection()->getDatabaseName();

        if (! array_key_exists($databaseName, self::$readyByDatabase)) {
            self::$readyByDatabase[$databaseName] = $this->schemaReady();
        }

        if (self::$readyByDatabase[$databaseName]) {
            return $next($request);
        }

        $payload = [
            'databaseName' => $databaseName,
            'sqlFile' => 'MASTER_PRICECHANGENEW_9773_SEQUENCE_02_LARGE.sql',
        ];

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Price Change New Sequence 02 tables/columns are not installed in the active tenant database.',
                'database' => $databaseName,
                'sql_file' => $payload['sqlFile'],
            ], 503);
        }

        return response()->view('pricechangenew::setup.required', $payload, 503);
    }

    private function schemaReady(): bool
    {
        foreach ([
            'pcn_price_changes',
            'pcn_price_change_lines',
            'pcn_price_change_scopes',
            'pcn_price_change_audits',
            'pcn_price_change_settings',
            'pcn_number_sequences',
            'pcn_price_change_applications',
            'pcn_price_change_application_lines',
            'pcn_price_change_scope_prices',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        foreach ([
            'application_scope', 'approval_notes', 'rejection_reason', 'cancelled_by',
            'cancelled_at', 'last_attempt_at', 'application_attempts',
        ] as $column) {
            if (! Schema::hasColumn('pcn_price_changes', $column)) {
                return false;
            }
        }

        foreach ([
            'apply_status', 'apply_message',
            'actual_before_purchase_price_ex_tax', 'actual_before_purchase_price_inc_tax',
            'actual_before_sell_price_ex_tax', 'actual_before_sell_price_inc_tax',
            'actual_after_purchase_price_ex_tax', 'actual_after_purchase_price_inc_tax',
            'actual_after_sell_price_ex_tax', 'actual_after_sell_price_inc_tax',
            'applied_at',
        ] as $column) {
            if (! Schema::hasColumn('pcn_price_change_lines', $column)) {
                return false;
            }
        }

        return true;
    }
}
