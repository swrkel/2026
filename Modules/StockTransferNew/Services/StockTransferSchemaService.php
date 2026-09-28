<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockTransferSchemaService
{
    private const REQUIRED = [
        'stnew_stores',
        'stnew_stock_transfers',
        'stnew_stock_transfer_lines',
        'stnew_stock_transfer_audits',
        'stnew_stock_transfer_settings',
        'stnew_stock_movements',
        'stnew_stock_balances',
        'stnew_stock_transfer_alerts',
    ];

    /** @var array<string, bool> */
    private static array $ready = [];

    public function ensure(): void
    {
        $key = $this->connectionKey();
        if (isset(self::$ready[$key])) {
            return;
        }

        $this->createCoreTables();
        $this->addCoreColumns();
        $this->seedPermissions();

        $missing = $this->missingTables();
        if ($missing !== []) {
            throw new \RuntimeException('Required Stock Transfer New tables are missing: ' . implode(', ', $missing));
        }

        self::$ready[$key] = true;
    }

    public function missingTables(): array
    {
        return array_values(array_filter(self::REQUIRED, fn (string $table): bool => ! Schema::hasTable($table)));
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

    private function createCoreTables(): void
    {
        if (! Schema::hasTable('stnew_stores')) {
            Schema::create('stnew_stores', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->string('code')->nullable();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('stnew_stock_transfers')) {
            Schema::create('stnew_stock_transfers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->string('transfer_no');
                $table->string('dispatch_note_no')->nullable();
                $table->string('receive_note_no')->nullable();
                $table->date('transfer_date')->index();
                $table->unsignedBigInteger('from_location_id')->nullable()->index();
                $table->unsignedBigInteger('to_location_id')->nullable()->index();
                $table->unsignedBigInteger('from_store_id')->nullable()->index();
                $table->unsignedBigInteger('to_store_id')->nullable()->index();
                $table->string('status')->default('draft')->index();
                $table->string('approval_mode')->nullable();
                $table->unsignedBigInteger('approval_matrix_id')->nullable()->index();
                $table->unsignedInteger('current_approval_step')->default(0);
                $table->string('reconciliation_status')->nullable();
                $table->string('reconciliation_resolution')->nullable();
                $table->text('reconciliation_note')->nullable();
                $table->timestamp('reconciled_at')->nullable();
                $table->unsignedBigInteger('reconciled_by')->nullable();
                $table->text('reason')->nullable();
                $table->text('remarks')->nullable();
                $table->string('vehicle_no')->nullable();
                $table->string('driver_name')->nullable();
                $table->string('driver_mobile')->nullable();
                $table->text('approval_note')->nullable();
                $table->text('rejection_note')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->unsignedBigInteger('returned_by')->nullable();
                $table->unsignedBigInteger('rejected_by')->nullable();
                $table->unsignedBigInteger('dispatched_by')->nullable();
                $table->unsignedBigInteger('received_by')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('returned_at')->nullable();
                $table->timestamp('rejected_at')->nullable();
                $table->timestamp('dispatched_at')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unique(['business_id', 'transfer_no'], 'stnew_stock_transfers_business_transfer_unique');
            });
        }

        if (! Schema::hasTable('stnew_stock_transfer_lines')) {
            Schema::create('stnew_stock_transfer_lines', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('transfer_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->string('batch_no')->nullable();
                $table->date('expiry_date')->nullable();
                $table->decimal('qty_requested', 22, 4)->default(0);
                $table->decimal('qty_dispatched', 22, 4)->default(0);
                $table->decimal('qty_received', 22, 4)->default(0);
                $table->decimal('short_qty', 22, 4)->default(0);
                $table->decimal('excess_qty', 22, 4)->default(0);
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('line_total', 22, 4)->default(0);
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stnew_stock_transfer_audits')) {
            Schema::create('stnew_stock_transfer_audits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('transfer_id')->index();
                $table->unsignedBigInteger('business_id')->index();
                $table->string('action')->index();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stnew_stock_transfer_settings')) {
            Schema::create('stnew_stock_transfer_settings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->unique();
                $table->boolean('approval_required')->default(true);
                $table->boolean('allow_partial_receive')->default(true);
                $table->boolean('require_stock_before_dispatch')->default(false);
                $table->boolean('auto_generate_document_numbers')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stnew_stock_movements')) {
            Schema::create('stnew_stock_movements', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('stock_transfer_id')->index();
                $table->unsignedBigInteger('stock_transfer_line_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->unsignedBigInteger('from_location_id')->nullable()->index();
                $table->unsignedBigInteger('to_location_id')->nullable()->index();
                $table->unsignedBigInteger('from_store_id')->nullable()->index();
                $table->unsignedBigInteger('to_store_id')->nullable()->index();
                $table->string('movement_type', 30)->index();
                $table->decimal('quantity', 22, 4)->default(0);
                $table->decimal('unit_cost', 22, 4)->default(0);
                $table->decimal('total_cost', 22, 4)->default(0);
                $table->string('reference_no')->nullable()->index();
                $table->timestamp('movement_date')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stnew_stock_balances')) {
            Schema::create('stnew_stock_balances', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('business_location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->decimal('qty_on_hand', 22, 4)->default(0);
                $table->decimal('qty_in_transit', 22, 4)->default(0);
                $table->decimal('last_unit_cost', 22, 4)->default(0);
                $table->timestamp('last_movement_at')->nullable();
                $table->timestamps();
                $table->unique(
                    ['business_id', 'business_location_id', 'store_id', 'product_id', 'variation_id'],
                    'stnew_balances_unique'
                );
            });
        }

        if (! Schema::hasTable('stnew_stock_transfer_alerts')) {
            Schema::create('stnew_stock_transfer_alerts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('transfer_id')->index();
                $table->string('event')->index();
                $table->text('message')->nullable();
                $table->unsignedBigInteger('target_user_id')->nullable()->index();
                $table->boolean('is_read')->default(false)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    private function addCoreColumns(): void
    {
        $this->addColumns('stnew_stock_transfers', [
            'dispatch_note_no' => fn (Blueprint $t) => $t->string('dispatch_note_no')->nullable(),
            'receive_note_no' => fn (Blueprint $t) => $t->string('receive_note_no')->nullable(),
            'vehicle_no' => fn (Blueprint $t) => $t->string('vehicle_no')->nullable(),
            'driver_name' => fn (Blueprint $t) => $t->string('driver_name')->nullable(),
            'driver_mobile' => fn (Blueprint $t) => $t->string('driver_mobile')->nullable(),
            'approval_mode' => fn (Blueprint $t) => $t->string('approval_mode')->nullable(),
            'approval_matrix_id' => fn (Blueprint $t) => $t->unsignedBigInteger('approval_matrix_id')->nullable(),
            'current_approval_step' => fn (Blueprint $t) => $t->unsignedInteger('current_approval_step')->default(0),
            'returned_at' => fn (Blueprint $t) => $t->timestamp('returned_at')->nullable(),
            'returned_by' => fn (Blueprint $t) => $t->unsignedBigInteger('returned_by')->nullable(),
            'reconciliation_status' => fn (Blueprint $t) => $t->string('reconciliation_status')->nullable(),
            'reconciliation_resolution' => fn (Blueprint $t) => $t->string('reconciliation_resolution')->nullable(),
            'reconciliation_note' => fn (Blueprint $t) => $t->text('reconciliation_note')->nullable(),
            'reconciled_at' => fn (Blueprint $t) => $t->timestamp('reconciled_at')->nullable(),
            'reconciled_by' => fn (Blueprint $t) => $t->unsignedBigInteger('reconciled_by')->nullable(),
        ]);

        $this->addColumns('stnew_stock_transfer_lines', [
            'batch_no' => fn (Blueprint $t) => $t->string('batch_no')->nullable(),
            'expiry_date' => fn (Blueprint $t) => $t->date('expiry_date')->nullable(),
            'short_qty' => fn (Blueprint $t) => $t->decimal('short_qty', 22, 4)->default(0),
            'excess_qty' => fn (Blueprint $t) => $t->decimal('excess_qty', 22, 4)->default(0),
        ]);

        $this->addColumns('stnew_stock_transfer_settings', [
            'require_stock_before_dispatch' => fn (Blueprint $t) => $t->boolean('require_stock_before_dispatch')->default(false),
            'auto_generate_document_numbers' => fn (Blueprint $t) => $t->boolean('auto_generate_document_numbers')->default(true),
        ]);
    }

    /** @param array<string, callable(Blueprint): mixed> $definitions */
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

    private function seedPermissions(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        foreach ([
            'stock_transfer_new.transfers.view',
            'stock_transfer_new.transfers.create',
            'stock_transfer_new.transfers.edit',
            'stock_transfer_new.transfers.delete',
            'stock_transfer_new.approvals.approve',
            'stock_transfer_new.approvals.reject',
            'stock_transfer_new.dispatch',
            'stock_transfer_new.receive',
            'stock_transfer_new.reports.view',
            'stock_transfer_new.settings.manage',
        ] as $permission) {
            $exists = DB::table('permissions')
                ->where('name', $permission)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('permissions')->insert([
                    'name' => $permission,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
