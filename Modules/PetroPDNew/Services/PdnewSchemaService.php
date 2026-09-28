<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPDNew\Services\Source\PoneSourceReader;

class PdnewSchemaService
{
    private const INSPECTION_TTL_SECONDS = 300;

    /** @var array<string,mixed>|null */
    private ?array $requestInspection = null;

    public function __construct(private PoneSourceReader $sourceReader) {}

    public function moduleTables(): array
    {
        return [
            'pdnew_module_settings', 'pdnew_number_sequences',
            'pdnew_operator_mappings', 'pdnew_source_imports',
            'pdnew_source_snapshots', 'pdnew_settlements',
            'pdnew_settlement_sources', 'pdnew_settlement_pumps',
            'pdnew_settlement_meter_sales', 'pdnew_settlement_payments',
            'pdnew_settlement_payment_details',
            'pdnew_settlement_credit_sales',
            'pdnew_settlement_credit_sale_lines',
            'pdnew_settlement_other_sales',
            'pdnew_settlement_other_sale_lines',
            'pdnew_settlement_unload_stocks',
            'pdnew_settlement_unload_stock_lines',
            'pdnew_settlement_day_entries',
            'pdnew_settlement_collections',
            'pdnew_settlement_ledger_entries',
            'pdnew_settlement_recoveries',
            'pdnew_settlement_commissions',
            'pdnew_settlement_adjustments',
            'pdnew_settlement_approvals',
            'pdnew_settlement_status_histories',
            'pdnew_reconciliation_issues', 'pdnew_day_ends',
            'pdnew_day_end_settlements', 'pdnew_posting_batches',
            'pdnew_posting_lines', 'pdnew_integration_outbox',
            'pdnew_integration_logs', 'pdnew_notification_templates',
            'pdnew_notification_logs', 'pdnew_print_logs',
            'pdnew_audit_logs', 'pdnew_documents',
            'pdnew_saved_report_filters',
        ];
    }

    public function sourceTables(): array
    {
        return (array) config('petropdnew.source.required_tables', []);
    }

    public function missingModuleTables(): array
    {
        return $this->inspection()['missing_module_tables'];
    }

    public function missingSourceTables(): array
    {
        return $this->inspection()['missing_source_tables'];
    }

    public function missingSourceColumns(): array
    {
        return $this->inspection()['missing_source_columns'];
    }

    public function isInstalled(): bool
    {
        return (bool) $this->inspection()['installed'];
    }

    public function forgetInspection(): void
    {
        $this->requestInspection = null;
        Cache::forget($this->cacheKey());
    }

    /** @return array{missing_module_tables:array<int,string>,missing_source_tables:array<int,string>,missing_source_columns:array<int,string>,installed:bool} */
    private function inspection(): array
    {
        if ($this->requestInspection !== null) {
            return $this->requestInspection;
        }

        $cached = Cache::get($this->cacheKey());
        if (is_array($cached) && ($cached['installed'] ?? false) === true) {
            return $this->requestInspection = $cached;
        }

        $missingModuleTables = array_values(array_filter(
            $this->moduleTables(),
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        $missingSourceTables = array_values(array_filter(
            $this->sourceTables(),
            fn (string $table): bool => ! Schema::hasTable($table)
        ));

        $missingSourceColumns = [];
        foreach ($this->sourceReader->requiredColumns() as $table => $columns) {
            if (in_array($table, $missingSourceTables, true) || ! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missingSourceColumns[] = $table . '.' . $column;
                }
            }
        }

        $this->requestInspection = [
            'missing_module_tables' => $missingModuleTables,
            'missing_source_tables' => $missingSourceTables,
            'missing_source_columns' => $missingSourceColumns,
            'installed' => $missingModuleTables === []
                && $missingSourceTables === []
                && $missingSourceColumns === [],
        ];

        // Cache only a successful schema inspection. Missing-state results must
        // be rechecked immediately after the repair SQL is executed.
        if ($this->requestInspection['installed']) {
            Cache::put(
                $this->cacheKey(),
                $this->requestInspection,
                now()->addSeconds(self::INSPECTION_TTL_SECONDS)
            );
        } else {
            Cache::forget($this->cacheKey());
        }

        return $this->requestInspection;
    }

    private function cacheKey(): string
    {
        try {
            $database = (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $exception) {
            $database = (string) config('database.default', 'tenant');
        }

        return 'pdnew:schema:inspection:' . sha1($database . '|v3-source-repair');
    }
}
