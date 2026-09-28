<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAdjustmentSchemaService
{
    private const TABLES = [
        'san_stock_adjustment_reasons',
        'san_stock_adjustments',
        'san_stock_adjustment_lines',
        'san_stock_adjustment_movements',
        'san_stock_adjustment_audits',
        'san_stock_adjustment_settings',
        'san_stock_adjustment_account_mappings',
    ];

    /** @var array<string, bool> */
    private static array $ready = [];

    public function ensure(): void
    {
        $key = $this->connectionKey();
        if (isset(self::$ready[$key])) {
            return;
        }

        if (! (bool) config('stockadjustmentnew.auto_install_schema', true)) {
            if ($this->missingTables() !== []) {
                throw new \RuntimeException('Automatic Stock Adjustment New schema installation is disabled.');
            }

            self::$ready[$key] = true;
            return;
        }

        $this->createMissingTables();
        $this->addMissingColumns();
        $this->normaliseS592Data();
        $this->seedDefaultReasons();
        $this->seedPermissions();

        $missing = $this->missingTables();
        if ($missing !== []) {
            /*
             * Say WHY, not just WHAT.
             *
             * "Missing tables: san_stock_adjustment_audits" tells nobody why the
             * create failed, and the screen shown to the user repeats only that.
             * Attempting each missing table on its own and logging the driver
             * error turns a dead end into a message somebody can act on.
             */
            foreach ($missing as $missingTable) {
                try {
                    $this->createMissingTables();
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('StockAdjustmentNew: could not create a required table.', [
                        'table' => $missingTable,
                        'database' => $this->databaseName(),
                        'message' => $e->getMessage(),
                        'sql_state' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    ]);
                    break;
                }
            }

            $missing = $this->missingTables();
        }

        if ($missing !== []) {
            throw new \RuntimeException('Required Stock Adjustment New tables are still missing: ' . implode(', ', $missing));
        }

        self::$ready[$key] = true;
    }

    public function missingTables(): array
    {
        $missing = [];
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        return $missing;
    }

    public function databaseName(): ?string
    {
        try {
            $name = DB::connection()->getDatabaseName();
            return is_string($name) && $name !== '' ? $name : null;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function connectionKey(): string
    {
        try {
            return DB::connection()->getName() . ':' . (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $exception) {
            return 'default:unknown';
        }
    }

    private function createMissingTables(): void
    {
        if (! Schema::hasTable('san_stock_adjustment_reasons')) {
            Schema::create('san_stock_adjustment_reasons', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index('san_reason_business_idx');
                $table->string('name');
                $table->string('code', 50)->nullable()->index('san_reason_code_idx');
                $table->string('effect', 20)->default('both');
                $table->boolean('requires_approval')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('san_stock_adjustments')) {
            Schema::create('san_stock_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->string('adjustment_no');
                $table->date('adjustment_date')->index();
                $table->string('adjustment_type', 50)->default('quantity');
                $table->string('stock_adjustment_type', 20)->default('increase')->index('san_adj_stock_type_idx');
                $table->unsignedBigInteger('reason_id')->nullable()->index();
                $table->string('status', 30)->default('draft')->index();
                $table->decimal('total_qty', 22, 4)->default(0);
                $table->decimal('total_cost_amount', 22, 4)->default(0);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('approval_remarks')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->unsignedBigInteger('host_transaction_id')->nullable()->index('san_adj_host_transaction_idx');
                $table->json('posting_summary')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'adjustment_no'], 'san_adj_business_no_unique');
                $table->index(['business_id', 'location_id', 'store_id'], 'san_adj_scope_idx');
                $table->index(['business_id', 'status', 'adjustment_date'], 'san_adj_business_status_date_idx');
            });
        }

        if (! Schema::hasTable('san_stock_adjustment_lines')) {
            Schema::create('san_stock_adjustment_lines', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('adjustment_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->string('product_name')->nullable();
                $table->string('sku')->nullable();
                $table->string('batch_no')->nullable()->index();
                $table->date('expiry_date')->nullable();
                $table->decimal('system_qty', 22, 4)->default(0);
                $table->decimal('counted_qty', 22, 4)->default(0);
                $table->decimal('adjustment_qty', 22, 4)->default(0);
                $table->string('stock_adjustment_type', 20)->default('increase')->index('san_line_stock_type_idx');
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('cost_amount', 22, 4)->default(0);
                $table->text('line_notes')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'variation_id'], 'san_line_product_idx');
                $table->index(['product_id', 'batch_no', 'expiry_date'], 'san_line_product_batch_expiry_idx');
            });
        }

        if (! Schema::hasTable('san_stock_adjustment_movements')) {
            Schema::create('san_stock_adjustment_movements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->unsignedBigInteger('adjustment_id')->index();
                $table->unsignedBigInteger('adjustment_line_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->string('batch_no')->nullable();
                $table->string('movement_type', 30)->index();
                $table->decimal('qty_change', 22, 4)->default(0);
                $table->decimal('cost_change', 22, 4)->default(0);
                $table->timestamp('movement_date')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['business_id', 'location_id', 'store_id', 'movement_date'], 'san_mov_scope_date_idx');
                $table->index(['product_id', 'variation_id'], 'san_mov_product_idx');
            });
        }



        if (! Schema::hasTable('san_stock_adjustment_settings')) {
            Schema::create('san_stock_adjustment_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->unique('san_settings_business_unique');
                $table->string('number_prefix', 30)->default('SAN-');
                $table->unsignedTinyInteger('number_padding')->default(5);
                $table->string('default_adjustment_type', 30)->default('quantity');
                $table->unsignedSmallInteger('default_page_size')->default(25);
                $table->unsignedTinyInteger('quantity_decimals')->default(4);
                $table->unsignedTinyInteger('amount_decimals')->default(4);
                $table->boolean('require_reason')->default(false);
                $table->boolean('require_location')->default(true);
                $table->boolean('require_store')->default(false);
                $table->boolean('require_approval')->default(true);
                $table->boolean('auto_submit')->default(false);
                $table->boolean('auto_post_after_approval')->default(false);
                $table->boolean('require_batch_when_available')->default(true);
                $table->boolean('hide_zero_stock_products')->default(false);
                $table->boolean('allow_negative_stock')->default(false);
                $table->boolean('allow_zero_unit_cost')->default(true);
                $table->boolean('allow_backdated_adjustments')->default(true);
                $table->unsignedInteger('max_backdate_days')->nullable();
                $table->boolean('allow_future_dated_adjustments')->default(true);
                $table->string('batch_selection_method', 20)->default('fefo');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('san_stock_adjustment_account_mappings')) {
            Schema::create('san_stock_adjustment_account_mappings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index('san_mapping_business_idx');
                $table->timestamp('effective_from')->nullable()->index('san_mapping_effective_idx');
                $table->string('adjustment_type', 30)->default('quantity')->index('san_mapping_type_idx');
                $table->unsignedBigInteger('category_id')->nullable()->index();
                $table->unsignedBigInteger('sub_category_id')->nullable()->index();
                $table->unsignedBigInteger('account_to_link_id')->nullable()->index();
                $table->unsignedBigInteger('increase_account_id')->nullable()->index('san_mapping_increase_account_idx');
                $table->unsignedBigInteger('decrease_account_id')->nullable()->index('san_mapping_decrease_account_idx');
                $table->unsignedBigInteger('stock_account_group_id')->nullable()->index();
                $table->unsignedBigInteger('stock_account_id')->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->index(
                    ['business_id', 'adjustment_type', 'category_id', 'sub_category_id', 'is_active'],
                    'san_mapping_lookup_idx'
                );
            });
        }

        if (! Schema::hasTable('san_stock_adjustment_audits')) {
            /*
             * Named indexes, matching every other table in this module and the
             * shipped SQL (san_audit_business_idx etc.).
             *
             * This was the only create() here using bare ->index(), which lets
             * Laravel generate names like
             * san_stock_adjustment_audits_adjustment_id_index. Those are long,
             * and they differ from the names SQL/10_MASTER_INSTALL.sql uses - so
             * a tenant part-installed by one route and part by the other can end
             * up with a clash that aborts the CREATE and leaves this table, and
             * only this table, missing.
             */
            Schema::create('san_stock_adjustment_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->nullable()->index('san_audit_business_idx');
                $table->unsignedBigInteger('adjustment_id')->index('san_audit_adjustment_idx');
                $table->string('event', 80)->index('san_audit_event_idx');
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function addMissingColumns(): void
    {
        $this->addColumns('san_stock_adjustment_reasons', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->nullable(),
            'name' => fn (Blueprint $t) => $t->string('name'),
            'code' => fn (Blueprint $t) => $t->string('code', 50)->nullable(),
            'effect' => fn (Blueprint $t) => $t->string('effect', 20)->default('both'),
            'requires_approval' => fn (Blueprint $t) => $t->boolean('requires_approval')->default(true),
            'is_active' => fn (Blueprint $t) => $t->boolean('is_active')->default(true),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);

        $this->addColumns('san_stock_adjustments', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->nullable(),
            'location_id' => fn (Blueprint $t) => $t->unsignedBigInteger('location_id')->nullable(),
            'store_id' => fn (Blueprint $t) => $t->unsignedBigInteger('store_id')->nullable(),
            'adjustment_no' => fn (Blueprint $t) => $t->string('adjustment_no')->default(''),
            'adjustment_date' => fn (Blueprint $t) => $t->date('adjustment_date')->nullable(),
            'adjustment_type' => fn (Blueprint $t) => $t->string('adjustment_type', 50)->default('quantity'),
            'stock_adjustment_type' => fn (Blueprint $t) => $t->string('stock_adjustment_type', 20)->default('increase'),
            'reason_id' => fn (Blueprint $t) => $t->unsignedBigInteger('reason_id')->nullable(),
            'status' => fn (Blueprint $t) => $t->string('status', 30)->default('draft'),
            'total_qty' => fn (Blueprint $t) => $t->decimal('total_qty', 22, 4)->default(0),
            'total_cost_amount' => fn (Blueprint $t) => $t->decimal('total_cost_amount', 22, 4)->default(0),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'created_by' => fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
            'approved_by' => fn (Blueprint $t) => $t->unsignedBigInteger('approved_by')->nullable(),
            'approved_at' => fn (Blueprint $t) => $t->timestamp('approved_at')->nullable(),
            'approval_remarks' => fn (Blueprint $t) => $t->text('approval_remarks')->nullable(),
            'posted_by' => fn (Blueprint $t) => $t->unsignedBigInteger('posted_by')->nullable(),
            'posted_at' => fn (Blueprint $t) => $t->timestamp('posted_at')->nullable(),
            'host_transaction_id' => fn (Blueprint $t) => $t->unsignedBigInteger('host_transaction_id')->nullable(),
            'posting_summary' => fn (Blueprint $t) => $t->json('posting_summary')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);

        $this->addColumns('san_stock_adjustment_lines', [
            'adjustment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('adjustment_id')->default(0),
            'product_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_id')->default(0),
            'variation_id' => fn (Blueprint $t) => $t->unsignedBigInteger('variation_id')->nullable(),
            'product_name' => fn (Blueprint $t) => $t->string('product_name')->nullable(),
            'sku' => fn (Blueprint $t) => $t->string('sku')->nullable(),
            'batch_no' => fn (Blueprint $t) => $t->string('batch_no')->nullable(),
            'expiry_date' => fn (Blueprint $t) => $t->date('expiry_date')->nullable(),
            'system_qty' => fn (Blueprint $t) => $t->decimal('system_qty', 22, 4)->default(0),
            'counted_qty' => fn (Blueprint $t) => $t->decimal('counted_qty', 22, 4)->default(0),
            'adjustment_qty' => fn (Blueprint $t) => $t->decimal('adjustment_qty', 22, 4)->default(0),
            'stock_adjustment_type' => fn (Blueprint $t) => $t->string('stock_adjustment_type', 20)->default('increase'),
            'unit_cost' => fn (Blueprint $t) => $t->decimal('unit_cost', 22, 4)->default(0),
            'cost_amount' => fn (Blueprint $t) => $t->decimal('cost_amount', 22, 4)->default(0),
            'line_notes' => fn (Blueprint $t) => $t->text('line_notes')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);

        $this->addColumns('san_stock_adjustment_movements', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->nullable(),
            'location_id' => fn (Blueprint $t) => $t->unsignedBigInteger('location_id')->nullable(),
            'store_id' => fn (Blueprint $t) => $t->unsignedBigInteger('store_id')->nullable(),
            'adjustment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('adjustment_id')->default(0),
            'adjustment_line_id' => fn (Blueprint $t) => $t->unsignedBigInteger('adjustment_line_id')->default(0),
            'product_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_id')->default(0),
            'variation_id' => fn (Blueprint $t) => $t->unsignedBigInteger('variation_id')->nullable(),
            'batch_no' => fn (Blueprint $t) => $t->string('batch_no')->nullable(),
            'movement_type' => fn (Blueprint $t) => $t->string('movement_type', 30)->default('increase'),
            'qty_change' => fn (Blueprint $t) => $t->decimal('qty_change', 22, 4)->default(0),
            'cost_change' => fn (Blueprint $t) => $t->decimal('cost_change', 22, 4)->default(0),
            'movement_date' => fn (Blueprint $t) => $t->timestamp('movement_date')->nullable(),
            'created_by' => fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);



        $this->addColumns('san_stock_adjustment_settings', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->default(0),
            'number_prefix' => fn (Blueprint $t) => $t->string('number_prefix', 30)->default('SAN-'),
            'number_padding' => fn (Blueprint $t) => $t->unsignedTinyInteger('number_padding')->default(5),
            'default_adjustment_type' => fn (Blueprint $t) => $t->string('default_adjustment_type', 30)->default('quantity'),
            'default_page_size' => fn (Blueprint $t) => $t->unsignedSmallInteger('default_page_size')->default(25),
            'quantity_decimals' => fn (Blueprint $t) => $t->unsignedTinyInteger('quantity_decimals')->default(4),
            'amount_decimals' => fn (Blueprint $t) => $t->unsignedTinyInteger('amount_decimals')->default(4),
            'require_reason' => fn (Blueprint $t) => $t->boolean('require_reason')->default(false),
            'require_location' => fn (Blueprint $t) => $t->boolean('require_location')->default(true),
            'require_store' => fn (Blueprint $t) => $t->boolean('require_store')->default(false),
            'require_approval' => fn (Blueprint $t) => $t->boolean('require_approval')->default(true),
            'auto_submit' => fn (Blueprint $t) => $t->boolean('auto_submit')->default(false),
            'auto_post_after_approval' => fn (Blueprint $t) => $t->boolean('auto_post_after_approval')->default(false),
            'require_batch_when_available' => fn (Blueprint $t) => $t->boolean('require_batch_when_available')->default(true),
            'hide_zero_stock_products' => fn (Blueprint $t) => $t->boolean('hide_zero_stock_products')->default(false),
            'allow_negative_stock' => fn (Blueprint $t) => $t->boolean('allow_negative_stock')->default(false),
            'allow_zero_unit_cost' => fn (Blueprint $t) => $t->boolean('allow_zero_unit_cost')->default(true),
            'allow_backdated_adjustments' => fn (Blueprint $t) => $t->boolean('allow_backdated_adjustments')->default(true),
            'max_backdate_days' => fn (Blueprint $t) => $t->unsignedInteger('max_backdate_days')->nullable(),
            'allow_future_dated_adjustments' => fn (Blueprint $t) => $t->boolean('allow_future_dated_adjustments')->default(true),
            'batch_selection_method' => fn (Blueprint $t) => $t->string('batch_selection_method', 20)->default('fefo'),
            'created_by' => fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn (Blueprint $t) => $t->unsignedBigInteger('updated_by')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);

        $this->addColumns('san_stock_adjustment_account_mappings', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->default(0),
            'effective_from' => fn (Blueprint $t) => $t->timestamp('effective_from')->nullable(),
            'adjustment_type' => fn (Blueprint $t) => $t->string('adjustment_type', 30)->default('quantity'),
            'category_id' => fn (Blueprint $t) => $t->unsignedBigInteger('category_id')->nullable(),
            'sub_category_id' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_category_id')->nullable(),
            'account_to_link_id' => fn (Blueprint $t) => $t->unsignedBigInteger('account_to_link_id')->nullable(),
            'increase_account_id' => fn (Blueprint $t) => $t->unsignedBigInteger('increase_account_id')->nullable()->index('san_mapping_increase_account_idx'),
            'decrease_account_id' => fn (Blueprint $t) => $t->unsignedBigInteger('decrease_account_id')->nullable()->index('san_mapping_decrease_account_idx'),
            'stock_account_group_id' => fn (Blueprint $t) => $t->unsignedBigInteger('stock_account_group_id')->nullable(),
            'stock_account_id' => fn (Blueprint $t) => $t->unsignedBigInteger('stock_account_id')->nullable(),
            'is_active' => fn (Blueprint $t) => $t->boolean('is_active')->default(true),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            'created_by' => fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn (Blueprint $t) => $t->unsignedBigInteger('updated_by')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);

        $this->addColumns('san_stock_adjustment_audits', [
            'business_id' => fn (Blueprint $t) => $t->unsignedBigInteger('business_id')->nullable(),
            'adjustment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('adjustment_id')->default(0),
            'event' => fn (Blueprint $t) => $t->string('event', 80)->default('unknown'),
            'payload' => fn (Blueprint $t) => $t->json('payload')->nullable(),
            'created_by' => fn (Blueprint $t) => $t->unsignedBigInteger('created_by')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ]);
    }


    /**
     * Bring partially installed tenant databases to the S592 data contract
     * without changing the original Quantity/Value/Damage/Expiry Type field.
     */
    private function normaliseS592Data(): void
    {
        if (Schema::hasTable('san_stock_adjustment_lines')
            && Schema::hasColumn('san_stock_adjustment_lines', 'stock_adjustment_type')
            && Schema::hasColumn('san_stock_adjustment_lines', 'adjustment_qty')) {
            DB::table('san_stock_adjustment_lines')
                ->where(function ($query): void {
                    $query->whereNull('stock_adjustment_type')
                        ->orWhereNotIn('stock_adjustment_type', ['increase', 'decrease'])
                        ->orWhere(function ($mismatch): void {
                            $mismatch->where('stock_adjustment_type', 'increase')
                                ->where('adjustment_qty', '<', 0);
                        })
                        ->orWhere(function ($mismatch): void {
                            $mismatch->where('stock_adjustment_type', 'decrease')
                                ->where('adjustment_qty', '>', 0);
                        });
                })
                ->update([
                    'stock_adjustment_type' => DB::raw("CASE WHEN COALESCE(adjustment_qty, 0) < 0 THEN 'decrease' ELSE 'increase' END"),
                ]);
        }

        if (Schema::hasTable('san_stock_adjustments')
            && Schema::hasColumn('san_stock_adjustments', 'stock_adjustment_type')
            && Schema::hasColumn('san_stock_adjustments', 'total_qty')) {
            $driver = DB::connection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)
                && Schema::hasTable('san_stock_adjustment_lines')
                && Schema::hasColumn('san_stock_adjustment_lines', 'adjustment_id')
                && Schema::hasColumn('san_stock_adjustment_lines', 'adjustment_qty')) {
                DB::statement(<<<'SQL'
UPDATE san_stock_adjustments AS a
LEFT JOIN (
    SELECT adjustment_id,
           CASE
               WHEN MAX(CASE WHEN adjustment_qty > 0 THEN 1 ELSE 0 END) = 1
                AND MAX(CASE WHEN adjustment_qty < 0 THEN 1 ELSE 0 END) = 1 THEN 'mixed'
               WHEN MAX(CASE WHEN adjustment_qty < 0 THEN 1 ELSE 0 END) = 1 THEN 'decrease'
               ELSE 'increase'
           END AS derived_direction
    FROM san_stock_adjustment_lines
    GROUP BY adjustment_id
) AS d ON d.adjustment_id = a.id
SET a.stock_adjustment_type = COALESCE(
    d.derived_direction,
    CASE WHEN COALESCE(a.total_qty, 0) < 0 THEN 'decrease' ELSE 'increase' END
)
WHERE NOT (
    a.stock_adjustment_type <=> COALESCE(
        d.derived_direction,
        CASE WHEN COALESCE(a.total_qty, 0) < 0 THEN 'decrease' ELSE 'increase' END
    )
)
SQL
                );
            } else {
                $directions = DB::table('san_stock_adjustment_lines')
                    ->select('adjustment_id')
                    ->selectRaw('MAX(CASE WHEN adjustment_qty > 0 THEN 1 ELSE 0 END) AS has_increase')
                    ->selectRaw('MAX(CASE WHEN adjustment_qty < 0 THEN 1 ELSE 0 END) AS has_decrease')
                    ->groupBy('adjustment_id')
                    ->get();

                foreach ($directions as $row) {
                    $direction = $row->has_increase && $row->has_decrease
                        ? 'mixed'
                        : ($row->has_decrease ? 'decrease' : 'increase');
                    DB::table('san_stock_adjustments')
                        ->where('id', $row->adjustment_id)
                        ->where('stock_adjustment_type', '<>', $direction)
                        ->update(['stock_adjustment_type' => $direction]);
                }

                DB::table('san_stock_adjustments')
                    ->whereNotIn('id', $directions->pluck('adjustment_id')->all())
                    ->where(function ($query): void {
                        $query->whereNull('stock_adjustment_type')
                            ->orWhereNotIn('stock_adjustment_type', ['increase', 'decrease', 'mixed']);
                    })
                    ->update([
                        'stock_adjustment_type' => DB::raw("CASE WHEN COALESCE(total_qty, 0) < 0 THEN 'decrease' ELSE 'increase' END"),
                    ]);
            }
        }

        if (Schema::hasTable('san_stock_adjustment_settings')) {
            if (Schema::hasColumn('san_stock_adjustment_settings', 'require_location')) {
                $locationUpdates = ['require_location' => 1];
                if (Schema::hasColumn('san_stock_adjustment_settings', 'updated_at')) {
                    $locationUpdates['updated_at'] = now();
                }

                DB::table('san_stock_adjustment_settings')
                    ->where(function ($query): void {
                        $query->whereNull('require_location')->orWhere('require_location', '<>', 1);
                    })
                    ->update($locationUpdates);
            }

            if (Schema::hasColumn('san_stock_adjustment_settings', 'default_adjustment_type')) {
                $typeUpdates = ['default_adjustment_type' => 'quantity'];
                if (Schema::hasColumn('san_stock_adjustment_settings', 'updated_at')) {
                    $typeUpdates['updated_at'] = now();
                }

                DB::table('san_stock_adjustment_settings')
                    ->where(function ($query): void {
                        $query->whereNull('default_adjustment_type')
                            ->orWhereNotIn('default_adjustment_type', ['quantity', 'value', 'damage', 'expiry']);
                    })
                    ->update($typeUpdates);
            }
        }
    }

    /**
     * @param array<string, callable(Blueprint): mixed> $definitions
     */
    private function addColumns(string $table, array $definitions): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($definitions as $column => $definition) {
            if (Schema::hasColumn($table, $column)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($definition): void {
                $definition($blueprint);
            });
        }
    }

    private function seedDefaultReasons(): void
    {
        if (! Schema::hasTable('san_stock_adjustment_reasons')) {
            return;
        }

        $rows = [
            ['name' => 'Physical stock count difference', 'code' => 'COUNT_DIFF', 'effect' => 'both'],
            ['name' => 'Damaged stock write-off', 'code' => 'DAMAGE', 'effect' => 'decrease'],
            ['name' => 'Expired stock write-off', 'code' => 'EXPIRY', 'effect' => 'decrease'],
            ['name' => 'System correction', 'code' => 'SYSTEM_CORRECTION', 'effect' => 'both'],
            ['name' => 'Opening balance correction', 'code' => 'OPENING_CORRECTION', 'effect' => 'both'],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('san_stock_adjustment_reasons')
                ->whereNull('business_id')
                ->where('code', $row['code'])
                ->first();

            $values = [
                'name' => $row['name'],
                'effect' => $row['effect'],
                'requires_approval' => 1,
                'is_active' => 1,
                'updated_at' => now(),
            ];

            if ($existing) {
                DB::table('san_stock_adjustment_reasons')->where('id', $existing->id)->update($values);
            } else {
                DB::table('san_stock_adjustment_reasons')->insert($values + [
                    'business_id' => null,
                    'code' => $row['code'],
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ([
            'stock_adjustment_new.view',
            'stock_adjustment_new.create',
            'stock_adjustment_new.edit',
            'stock_adjustment_new.submit',
            'stock_adjustment_new.approve',
            'stock_adjustment_new.reject',
            'stock_adjustment_new.post',
            'stock_adjustment_new.reports',
            'stock_adjustment_new.settings',
        ] as $permission) {
            $query = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web');

            if ($query->exists()) {
                $query->update(['updated_at' => now()]);
                continue;
            }

            DB::table('permissions')->insert([
                'name' => $permission,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
