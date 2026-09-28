<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'settlement_card_payments' => 'card',
        'settlement_cash_payments' => 'cash',
        'settlement_cheque_payments' => 'cheque',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        $this->ensureUnmatchedTable();

        foreach (self::TABLES as $table => $paymentType) {
            if (! $this->canBackfill($table)) {
                continue;
            }

            $this->backfillSimplePaymentTable($table, $paymentType);
            $this->logUnmatched($table);
        }

        if ($this->canBackfill('settlement_credit_sale_payments')) {
            $this->backfillCreditSalePayments();
            $this->logUnmatched('settlement_credit_sale_payments');
        }
    }

    public function down(): void
    {
        // Data backfill only. Intentionally not reversible.
    }

    private function backfillSimplePaymentTable(string $table, string $paymentType): void
    {
        DB::statement("
            UPDATE `{$table}` sp
            JOIN (
                SELECT s.sp_id, p.pop_id
                FROM (
                    SELECT
                        id AS sp_id,
                        business_id,
                        COALESCE(settlement_no, '') AS settlement_key,
                        CAST(amount AS DECIMAL(15,6)) AS amount_key,
                        ROW_NUMBER() OVER (
                            PARTITION BY business_id, COALESCE(settlement_no, ''), CAST(amount AS DECIMAL(15,6))
                            ORDER BY id
                        ) AS rn
                    FROM `{$table}`
                    WHERE pump_payment_id IS NULL
                ) s
                JOIN (
                    SELECT
                        id AS pop_id,
                        business_id,
                        COALESCE(settlement_no, '') AS settlement_key,
                        CAST(payment_amount AS DECIMAL(15,6)) AS amount_key,
                        ROW_NUMBER() OVER (
                            PARTITION BY business_id, COALESCE(settlement_no, ''), CAST(payment_amount AS DECIMAL(15,6))
                            ORDER BY id
                        ) AS rn
                    FROM pump_operator_payments
                    WHERE payment_type = '{$paymentType}'
                ) p
                  ON p.business_id = s.business_id
                 AND p.settlement_key COLLATE utf8mb4_unicode_ci = s.settlement_key COLLATE utf8mb4_unicode_ci
                 AND p.amount_key = s.amount_key
                 AND p.rn = s.rn
            ) matched ON matched.sp_id = sp.id
            LEFT JOIN `{$table}` existing
              ON existing.business_id = sp.business_id
             AND existing.pump_payment_id = matched.pop_id
             AND existing.id <> sp.id
             AND existing.settlement_no COLLATE utf8mb4_unicode_ci = sp.settlement_no COLLATE utf8mb4_unicode_ci
            SET sp.pump_payment_id = matched.pop_id
            WHERE sp.pump_payment_id IS NULL
              AND existing.id IS NULL
        ");
    }

    private function backfillCreditSalePayments(): void
    {
        DB::statement("
            UPDATE settlement_credit_sale_payments scsp
            JOIN (
                SELECT s.scsp_id, p.pop_id
                FROM (
                    SELECT
                        id AS scsp_id,
                        business_id,
                        pump_operator_id,
                        COALESCE(settlement_no, '') AS settlement_key,
                        COALESCE(collection_form_no, '') AS collection_key,
                        CAST(amount AS DECIMAL(15,6)) AS amount_key,
                        ROW_NUMBER() OVER (
                            PARTITION BY business_id, pump_operator_id, COALESCE(settlement_no, ''),
                                         COALESCE(collection_form_no, ''), CAST(amount AS DECIMAL(15,6))
                            ORDER BY id
                        ) AS rn
                    FROM settlement_credit_sale_payments
                    WHERE pump_payment_id IS NULL
                      AND is_from_pumper = 1
                ) s
                JOIN (
                    SELECT
                        id AS pop_id,
                        business_id,
                        pump_operator_id,
                        COALESCE(settlement_no, '') AS settlement_key,
                        COALESCE(collection_form_no, '') AS collection_key,
                        CAST(payment_amount AS DECIMAL(15,6)) AS amount_key,
                        ROW_NUMBER() OVER (
                            PARTITION BY business_id, pump_operator_id, COALESCE(settlement_no, ''),
                                         COALESCE(collection_form_no, ''), CAST(payment_amount AS DECIMAL(15,6))
                            ORDER BY id
                        ) AS rn
                    FROM pump_operator_payments
                    WHERE payment_type = 'credit'
                ) p
                  ON p.business_id = s.business_id
                 AND p.pump_operator_id = s.pump_operator_id
                 AND p.settlement_key COLLATE utf8mb4_unicode_ci = s.settlement_key COLLATE utf8mb4_unicode_ci
                 AND p.collection_key COLLATE utf8mb4_unicode_ci = s.collection_key COLLATE utf8mb4_unicode_ci
                 AND p.amount_key = s.amount_key
                 AND p.rn = s.rn
            ) matched ON matched.scsp_id = scsp.id
            LEFT JOIN settlement_credit_sale_payments existing
              ON existing.business_id = scsp.business_id
             AND existing.pump_payment_id = matched.pop_id
             AND existing.id <> scsp.id
             AND existing.settlement_no COLLATE utf8mb4_unicode_ci = scsp.settlement_no COLLATE utf8mb4_unicode_ci
            SET scsp.pump_payment_id = matched.pop_id
            WHERE scsp.pump_payment_id IS NULL
              AND existing.id IS NULL
        ");
    }

    private function ensureUnmatchedTable(): void
    {
        if (Schema::hasTable('petro_refactor_unmatched_payments')) {
            return;
        }

        DB::statement("
            CREATE TABLE petro_refactor_unmatched_payments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                table_name VARCHAR(64) NOT NULL,
                row_id INT UNSIGNED NOT NULL,
                reason VARCHAR(255) NOT NULL,
                created_at TIMESTAMP NULL,
                KEY idx_petro_refactor_unmatched_table_row (table_name, row_id)
            ) ENGINE=InnoDB
        ");
    }

    private function logUnmatched(string $table): void
    {
        DB::statement("
            INSERT INTO petro_refactor_unmatched_payments (table_name, row_id, reason, created_at)
            SELECT '{$table}', id, 'no pump_operator_payments match by composite key', NOW()
            FROM `{$table}` sp
            WHERE sp.pump_payment_id IS NULL
              AND NOT EXISTS (
                  SELECT 1 FROM petro_refactor_unmatched_payments u
                  WHERE u.table_name = '{$table}' AND u.row_id = sp.id
              )
        ");
    }

    private function canBackfill(string $table): bool
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, 'pump_payment_id');
    }
};
