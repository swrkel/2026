<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DETAIL_TABLES = [
        'settlement_cash_payments' => 'cash',
        'settlement_card_payments' => 'card',
        'settlement_cheque_payments' => 'cheque',
        'settlement_credit_sale_payments' => 'credit',
        'settlement_shortage_payments' => 'shortage',
        'settlement_excess_payments' => 'excess',
    ];

    private const OPERATIONAL_TABLES = [
        'daily_collections' => [
            'type' => 'cash',
            'operator_column' => 'pump_operator_id',
            'collection_column' => 'collection_form_no',
        ],
        'daily_cards' => [
            'type' => 'card',
            'operator_column' => 'pump_operator_id',
            'collection_column' => 'collection_no',
        ],
        'daily_cheque_payments' => [
            'type' => 'cheque',
            'operator_column' => 'pump_operator_id',
            'collection_column' => 'collection_form_no',
        ],
        'daily_vouchers' => [
            'type' => 'credit',
            'operator_column' => 'operator_id',
            'collection_column' => 'daily_vouchers_no',
        ],
    ];

    public function up(): void
    {
        foreach (self::DETAIL_TABLES as $table => $paymentType) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'pump_payment_id')) {
                    $blueprint->unsignedBigInteger('pump_payment_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn($table, 'shift_id')) {
                    $blueprint->unsignedBigInteger('shift_id')->nullable()->after('pump_payment_id');
                }
                if (! Schema::hasColumn($table, 'pump_operator_id')) {
                    $blueprint->unsignedBigInteger('pump_operator_id')->nullable()->after('shift_id');
                }
            });

            $this->addIndexIfMissing($table, $table . '_pump_payment_idx', ['pump_payment_id']);
            $this->addIndexIfMissing($table, $table . '_business_shift_idx', ['business_id', 'shift_id']);
            $this->addIndexIfMissing($table, $table . '_business_operator_shift_idx', ['business_id', 'pump_operator_id', 'shift_id']);

            $this->backfillFromMasterParent($table, $paymentType);
            $this->backfillCashCustomerPaymentLink($table, $paymentType);
        }

        foreach (self::OPERATIONAL_TABLES as $table => $config) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $operatorColumn = $config['operator_column'];
            Schema::table($table, function (Blueprint $blueprint) use ($table, $operatorColumn) {
                if (! Schema::hasColumn($table, 'pump_payment_id')) {
                    $blueprint->unsignedBigInteger('pump_payment_id')->nullable()->after('id');
                }
                if (! Schema::hasColumn($table, 'shift_id')) {
                    $blueprint->unsignedBigInteger('shift_id')->nullable()->after('pump_payment_id');
                }
                if (! Schema::hasColumn($table, $operatorColumn)) {
                    $blueprint->unsignedBigInteger($operatorColumn)->nullable()->after('shift_id');
                }
            });

            $this->addIndexIfMissing($table, $table . '_pump_payment_idx', ['pump_payment_id']);
            if (Schema::hasColumn($table, 'business_id')) {
                $this->addIndexIfMissing($table, $table . '_business_shift_idx', ['business_id', 'shift_id']);
                $this->addIndexIfMissing($table, $table . '_business_operator_shift_idx', ['business_id', $operatorColumn, 'shift_id']);
            }

            $this->backfillOperationalMasterLink($table, $config);
        }

        if (! Schema::hasTable('petro_pd_payment_reconciliation_events')) {
            Schema::create('petro_pd_payment_reconciliation_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('pump_operator_id')->nullable()->index();
                $table->string('shift_ids', 500)->nullable();
                $table->unsignedBigInteger('settlement_id')->nullable()->index();
                $table->string('settlement_no', 191)->nullable()->index();
                $table->unsignedBigInteger('pump_payment_id')->nullable()->index();
                $table->string('issue_type', 100)->index();
                $table->string('severity', 20)->default('critical')->index();
                $table->text('message');
                $table->longText('context_json')->nullable();
                $table->string('snapshot_fingerprint', 64)->nullable()->index();
                $table->string('event_hash', 64)->unique();
                $table->timestamp('resolved_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Financial links are additive and must not be removed on rollback.
        // Only the new audit event table can be safely removed.
        Schema::dropIfExists('petro_pd_payment_reconciliation_events');
    }

    private function backfillFromMasterParent(string $table, string $paymentType): void
    {
        if (! Schema::hasTable('pump_operator_payments')
            || ! Schema::hasColumn('pump_operator_payments', 'parent_id')) {
            return;
        }

        $tableName = str_replace('`', '``', $table);
        $type = str_replace("'", "''", $paymentType);

        DB::statement(<<<SQL
UPDATE `{$tableName}` detail
JOIN pump_operator_payments pop
  ON pop.business_id = detail.business_id
 AND pop.parent_id = detail.id
 AND LOWER(pop.payment_type) = '{$type}'
SET detail.pump_payment_id = pop.id,
    detail.shift_id = pop.shift_id
WHERE (detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0)
  AND pop.shift_id IS NOT NULL
  AND pop.shift_id > 0
SQL);

        DB::statement(<<<SQL
UPDATE `{$tableName}` detail
JOIN pump_operator_payments pop ON pop.id = detail.pump_payment_id
SET detail.shift_id = pop.shift_id,
    detail.pump_operator_id = pop.pump_operator_id
WHERE detail.pump_payment_id IS NOT NULL
  AND detail.pump_payment_id > 0
  AND ((detail.shift_id IS NULL OR detail.shift_id = 0)
       OR (detail.pump_operator_id IS NULL OR detail.pump_operator_id = 0))
SQL);
    }

    private function backfillCashCustomerPaymentLink(string $table, string $paymentType): void
    {
        if ($paymentType !== 'cash'
            || ! Schema::hasColumn($table, 'customer_payment_id')
            || ! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        DB::statement(<<<'SQL'
UPDATE settlement_cash_payments detail
JOIN pump_operator_payments pop
  ON pop.id = detail.customer_payment_id
 AND pop.business_id = detail.business_id
 AND LOWER(pop.payment_type) = 'cash'
SET detail.pump_payment_id = pop.id,
    detail.shift_id = pop.shift_id
WHERE (detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0)
  AND pop.shift_id IS NOT NULL
  AND pop.shift_id > 0
SQL);
    }

    private function backfillOperationalMasterLink(string $table, array $config): void
    {
        if (! Schema::hasTable('pump_operator_payments')
            || ! Schema::hasColumn($table, 'business_id')
            || ! Schema::hasColumn($table, $config['operator_column'])
            || ! Schema::hasColumn($table, $config['collection_column'])) {
            return;
        }

        $tableName = str_replace('`', '``', $table);
        $operatorColumn = str_replace('`', '``', $config['operator_column']);
        $collectionColumn = str_replace('`', '``', $config['collection_column']);
        $type = str_replace("'", "''", $config['type']);
        $typeSql = match ($type) {
            'card' => "'card','cards'",
            'cheque' => "'cheque','cheques'",
            'credit' => "'credit','multiple_credit'",
            default => "'{$type}'",
        };

        // Cheques already carry the strongest direct legacy relationship.
        if ($table === 'daily_cheque_payments' && Schema::hasColumn($table, 'linked_payment_id')) {
            DB::statement(<<<SQL
UPDATE `{$tableName}` detail
JOIN pump_operator_payments pop
  ON pop.id = detail.linked_payment_id
 AND pop.business_id = detail.business_id
 AND LOWER(pop.payment_type) IN ('cheque','cheques')
SET detail.pump_payment_id = pop.id,
    detail.shift_id = pop.shift_id
WHERE (detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0)
  AND pop.shift_id IS NOT NULL
  AND pop.shift_id > 0
SQL);
        }

        // Legacy rows are linked only when business/operator/collection resolves
        // to exactly one master payment. Ambiguous records are deliberately left
        // for the audit instead of being attached to a guessed Shift ID.
        DB::statement(<<<SQL
UPDATE `{$tableName}` detail
JOIN (
    SELECT business_id, pump_operator_id, collection_form_no,
           MIN(id) AS pump_payment_id, COUNT(*) AS payment_count
    FROM pump_operator_payments
    WHERE LOWER(payment_type) IN ({$typeSql})
      AND collection_form_no IS NOT NULL
      AND collection_form_no <> ''
    GROUP BY business_id, pump_operator_id, collection_form_no
    HAVING COUNT(*) = 1
) exact_payment
  ON exact_payment.business_id = detail.business_id
 AND exact_payment.pump_operator_id = detail.`{$operatorColumn}`
 AND CAST(exact_payment.collection_form_no AS CHAR) = CAST(detail.`{$collectionColumn}` AS CHAR)
SET detail.pump_payment_id = exact_payment.pump_payment_id
WHERE detail.pump_payment_id IS NULL OR detail.pump_payment_id = 0
SQL);

        DB::statement(<<<SQL
UPDATE `{$tableName}` detail
JOIN pump_operator_payments pop ON pop.id = detail.pump_payment_id
SET detail.shift_id = pop.shift_id,
    detail.`{$operatorColumn}` = pop.pump_operator_id
WHERE detail.pump_payment_id IS NOT NULL
  AND detail.pump_payment_id > 0
  AND pop.shift_id IS NOT NULL
  AND pop.shift_id > 0
  AND ((detail.shift_id IS NULL OR detail.shift_id = 0)
       OR (detail.`{$operatorColumn}` IS NULL OR detail.`{$operatorColumn}` = 0))
SQL);
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
