<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pump_operator_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                $table->decimal('gross_amount', 20, 4)->nullable()->after('payment_amount');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                $table->decimal('discount_amount', 20, 4)->nullable()->after('gross_amount');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                $table->decimal('net_amount', 20, 4)->nullable()->after('discount_amount');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'source_type')) {
                $table->string('source_type', 50)->nullable()->after('net_amount');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'source_id')) {
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable()->after('source_id');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                $table->date('transaction_date')->nullable()->after('customer_id');
            }
            if (! Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                $table->string('reference_no', 191)->nullable()->after('transaction_date');
            }
        });

        Schema::table('settlement_credit_sale_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                $table->unsignedBigInteger('pump_payment_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                $table->unsignedBigInteger('shift_id')->nullable()->after('pump_operator_id');
            }
        });

        $this->addIndexIfMissing(
            'pump_operator_payments',
            'pop_business_operator_shift_type_idx',
            ['business_id', 'pump_operator_id', 'shift_id', 'payment_type']
        );
        $this->addIndexIfMissing(
            'pump_operator_payments',
            'pop_source_type_id_idx',
            ['source_type', 'source_id']
        );
        $this->addIndexIfMissing(
            'settlement_credit_sale_payments',
            'scsp_pump_payment_id_idx',
            ['pump_payment_id']
        );
        $this->addIndexIfMissing(
            'settlement_credit_sale_payments',
            'scsp_business_operator_shift_idx',
            ['business_id', 'pump_operator_id', 'shift_id']
        );

        // Recover the detail Shift ID from its original Daily Voucher when
        // available. This is a source relationship, not a latest-shift guess.
        if (Schema::hasTable('daily_vouchers')
            && Schema::hasColumn('settlement_credit_sale_payments', 'daily_voucher_id')
            && Schema::hasColumn('daily_vouchers', 'shift_id')) {
            DB::statement(<<<'SQL'
UPDATE settlement_credit_sale_payments scsp
JOIN daily_vouchers dv ON dv.id = scsp.daily_voucher_id
SET scsp.shift_id = dv.shift_id
WHERE (scsp.shift_id IS NULL OR scsp.shift_id = 0)
  AND dv.shift_id IS NOT NULL
  AND dv.shift_id > 0
SQL);
        }

        // First use the strongest legacy key: exact business/operator/Shift/
        // collection number with exactly one master candidate.
        DB::statement(<<<'SQL'
UPDATE settlement_credit_sale_payments scsp
JOIN (
    SELECT
        business_id,
        pump_operator_id,
        shift_id,
        collection_form_no,
        MIN(id) AS pump_payment_id,
        COUNT(*) AS payment_count
    FROM pump_operator_payments
    WHERE LOWER(payment_type) = 'credit'
      AND shift_id IS NOT NULL
      AND shift_id > 0
      AND collection_form_no IS NOT NULL
      AND collection_form_no <> ''
    GROUP BY business_id, pump_operator_id, shift_id, collection_form_no
    HAVING COUNT(*) = 1
) pop_exact
  ON pop_exact.business_id = scsp.business_id
 AND pop_exact.pump_operator_id = scsp.pump_operator_id
 AND pop_exact.shift_id = scsp.shift_id
 AND CAST(pop_exact.collection_form_no AS CHAR) = CAST(scsp.collection_form_no AS CHAR)
SET scsp.pump_payment_id = pop_exact.pump_payment_id
WHERE scsp.pump_payment_id IS NULL
SQL);

        // Link remaining rows only when the collection number is globally
        // unambiguous for the business and operator. Never guess otherwise.
        DB::statement(<<<'SQL'
UPDATE settlement_credit_sale_payments scsp
JOIN (
    SELECT
        business_id,
        pump_operator_id,
        collection_form_no,
        MIN(id) AS pump_payment_id,
        COUNT(*) AS payment_count
    FROM pump_operator_payments
    WHERE LOWER(payment_type) = 'credit'
      AND collection_form_no IS NOT NULL
      AND collection_form_no <> ''
    GROUP BY business_id, pump_operator_id, collection_form_no
    HAVING COUNT(*) = 1
) pop_unique
  ON pop_unique.business_id = scsp.business_id
 AND pop_unique.pump_operator_id = scsp.pump_operator_id
 AND CAST(pop_unique.collection_form_no AS CHAR) = CAST(scsp.collection_form_no AS CHAR)
SET scsp.pump_payment_id = pop_unique.pump_payment_id
WHERE scsp.pump_payment_id IS NULL
SQL);

        // The master payment owns the immutable Shift ID.
        DB::statement(<<<'SQL'
UPDATE settlement_credit_sale_payments scsp
JOIN pump_operator_payments pop ON pop.id = scsp.pump_payment_id
SET scsp.shift_id = pop.shift_id
WHERE scsp.pump_payment_id IS NOT NULL
  AND (scsp.shift_id IS NULL OR scsp.shift_id = 0)
SQL);

        // Every non-credit payment has no discount; its existing payment_amount
        // is both gross and net. This keeps all payment types readable from the
        // same authoritative table without changing legacy payment_amount.
        DB::statement(<<<'SQL'
UPDATE pump_operator_payments
SET gross_amount = COALESCE(gross_amount, payment_amount),
    discount_amount = COALESCE(discount_amount, 0),
    net_amount = COALESCE(net_amount, payment_amount),
    source_type = COALESCE(source_type, 'pumper_dashboard')
WHERE LOWER(payment_type) <> 'credit'
SQL);

        // For credit sales, retain payment_amount as the legacy-compatible gross
        // value and store the authoritative gross/discount/net values alongside it.
        DB::statement(<<<'SQL'
UPDATE pump_operator_payments pop
LEFT JOIN (
    SELECT
        pump_payment_id,
        MIN(COALESCE(total_discount, 0)) AS min_discount,
        MAX(COALESCE(total_discount, 0)) AS max_discount,
        MIN(COALESCE(sub_total, amount - COALESCE(total_discount, 0))) AS min_net,
        MAX(COALESCE(sub_total, amount - COALESCE(total_discount, 0))) AS max_net,
        COUNT(*) AS detail_count,
        MIN(id) AS first_detail_id
    FROM settlement_credit_sale_payments
    WHERE pump_payment_id IS NOT NULL
    GROUP BY pump_payment_id
) credit_detail ON credit_detail.pump_payment_id = pop.id
SET pop.gross_amount = COALESCE(pop.gross_amount, pop.payment_amount),
    pop.discount_amount = COALESCE(
        pop.discount_amount,
        CASE
            WHEN ABS(credit_detail.max_discount - credit_detail.min_discount) < 0.02
                THEN credit_detail.max_discount
            ELSE NULL
        END
    ),
    pop.net_amount = COALESCE(
        pop.net_amount,
        CASE
            WHEN ABS(credit_detail.max_net - credit_detail.min_net) < 0.02
                THEN credit_detail.max_net
            ELSE NULL
        END
    ),
    pop.source_type = COALESCE(pop.source_type, 'credit_sale'),
    pop.source_id = COALESCE(
        pop.source_id,
        CASE WHEN credit_detail.detail_count = 1 THEN credit_detail.first_detail_id ELSE NULL END
    )
WHERE LOWER(pop.payment_type) = 'credit'
SQL);

        // Backfill transaction-level credit metadata only from the exact linked
        // detail. Product lines remain in their purpose-built detail tables.
        DB::statement(<<<'SQL'
UPDATE pump_operator_payments pop
JOIN settlement_credit_sale_payments scsp ON scsp.pump_payment_id = pop.id
SET pop.customer_id = COALESCE(pop.customer_id, scsp.customer_id),
    pop.transaction_date = COALESCE(pop.transaction_date, scsp.order_date, DATE(pop.date_and_time), DATE(pop.created_at)),
    pop.reference_no = COALESCE(
        NULLIF(pop.reference_no, ''),
        NULLIF(scsp.bill_number, ''),
        NULLIF(scsp.order_number, '')
    )
WHERE LOWER(pop.payment_type) = 'credit'
SQL);
    }

    public function down(): void
    {
        // This migration is intentionally additive and data-protective. Columns
        // may have existed in older tenant databases, so rollback must never
        // remove financial or Shift ID data. Only indexes created with the
        // migration-specific names are removed.
        $this->dropIndexIfExists('settlement_credit_sale_payments', 'scsp_business_operator_shift_idx');
        $this->dropIndexIfExists('settlement_credit_sale_payments', 'scsp_pump_payment_id_idx');
        $this->dropIndexIfExists('pump_operator_payments', 'pop_source_type_id_idx');
        $this->dropIndexIfExists('pump_operator_payments', 'pop_business_operator_shift_type_idx');
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $database = DB::connection()->getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
