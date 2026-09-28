<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'settlement_excess_payments';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        $addPumpPaymentId = ! Schema::hasColumn(self::TABLE, 'pump_payment_id');
        $addShiftId = ! Schema::hasColumn(self::TABLE, 'shift_id');
        $addPumpOperatorId = ! Schema::hasColumn(self::TABLE, 'pump_operator_id');

        if ($addPumpPaymentId || $addShiftId || $addPumpOperatorId) {
            Schema::table(self::TABLE, function (Blueprint $table) use (
                $addPumpPaymentId,
                $addShiftId,
                $addPumpOperatorId
            ): void {
                if ($addPumpPaymentId) {
                    $table->unsignedBigInteger('pump_payment_id')->nullable()->after('id');
                }
                if ($addShiftId) {
                    $table->unsignedBigInteger('shift_id')->nullable()->after('pump_payment_id');
                }
                if ($addPumpOperatorId) {
                    $table->unsignedBigInteger('pump_operator_id')->nullable()->after('shift_id');
                }
            });
        }

        $this->addIndexIfMissing(
            'settlement_excess_payments_pump_payment_idx',
            ['pump_payment_id']
        );
        $this->addIndexIfMissing(
            'settlement_excess_payments_business_shift_idx',
            ['business_id', 'shift_id']
        );
        $this->addIndexIfMissing(
            'settlement_excess_payments_business_operator_shift_idx',
            ['business_id', 'pump_operator_id', 'shift_id']
        );

        $this->backfillAuthoritativeLinks();
    }

    public function down(): void
    {
        // Financial identity columns are additive and intentionally retained.
    }

    private function addIndexIfMissing(string $indexName, array $columns): void
    {
        $existing = DB::select(
            'SELECT 1 FROM information_schema.statistics '
            . 'WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
            [self::TABLE, $indexName]
        );

        if (! empty($existing)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($indexName, $columns): void {
            $table->index($columns, $indexName);
        });
    }

    private function backfillAuthoritativeLinks(): void
    {
        if (! Schema::hasTable('pump_operator_payments')
            || ! Schema::hasColumn('pump_operator_payments', 'id')
            || ! Schema::hasColumn('pump_operator_payments', 'business_id')
            || ! Schema::hasColumn('pump_operator_payments', 'shift_id')
            || ! Schema::hasColumn('pump_operator_payments', 'pump_operator_id')) {
            return;
        }

        if (Schema::hasColumn('pump_operator_payments', 'parent_id')
            && Schema::hasColumn('pump_operator_payments', 'payment_type')) {
            DB::statement(<<<'SQL'
UPDATE settlement_excess_payments detail
JOIN pump_operator_payments master
  ON master.business_id = detail.business_id
 AND master.parent_id = detail.id
 AND LOWER(master.payment_type) = 'excess'
SET detail.pump_payment_id = master.id,
    detail.shift_id = master.shift_id,
    detail.pump_operator_id = master.pump_operator_id
WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0
SQL);
        }

        DB::statement(<<<'SQL'
UPDATE settlement_excess_payments detail
JOIN pump_operator_payments master
  ON master.id = detail.pump_payment_id
 AND master.business_id = detail.business_id
SET detail.shift_id = master.shift_id,
    detail.pump_operator_id = master.pump_operator_id
WHERE detail.pump_payment_id IS NOT NULL
  AND detail.pump_payment_id > 0
  AND ((detail.shift_id IS NULL OR detail.shift_id = 0)
       OR (detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0))
SQL);
    }
};
