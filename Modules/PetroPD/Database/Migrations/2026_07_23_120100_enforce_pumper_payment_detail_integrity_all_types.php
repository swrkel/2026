<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const AUTHORITY_TABLES = [
        'settlement_cash_payments' => 'pump_operator_id',
        'settlement_card_payments' => 'pump_operator_id',
        'settlement_cheque_payments' => 'pump_operator_id',
        'settlement_credit_sale_payments' => 'pump_operator_id',
        'settlement_shortage_payments' => 'pump_operator_id',
        'settlement_excess_payments' => 'pump_operator_id',
        'daily_collections' => 'pump_operator_id',
        'daily_cards' => 'pump_operator_id',
        'daily_cheque_payments' => 'pump_operator_id',
        'daily_vouchers' => 'operator_id',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropTriggers();
        $this->createMasterInsertGuard();
        $this->createMasterUpdateGuard();

        foreach (self::AUTHORITY_TABLES as $table => $operatorColumn) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'pump_payment_id')
                || ! Schema::hasColumn($table, 'shift_id')) {
                continue;
            }

            $this->createDetailTrigger($table, $operatorColumn, 'INSERT');
            $this->createDetailTrigger($table, $operatorColumn, 'UPDATE');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropTriggers();
    }

    private function createMasterInsertGuard(): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        $hasSourceColumns = Schema::hasColumn('pump_operator_payments', 'source_type')
            && Schema::hasColumn('pump_operator_payments', 'source_id');

        $sourceGuard = $hasSourceColumns ? <<<'SQL'

    IF NEW.source_type IS NOT NULL
       AND NEW.source_type <> ''
       AND NEW.source_id IS NOT NULL
       AND NEW.source_id > 0
       AND EXISTS (
            SELECT 1
              FROM pump_operator_payments existing_payment
             WHERE existing_payment.business_id = NEW.business_id
               AND existing_payment.source_type = NEW.source_type
               AND existing_payment.source_id = NEW.source_id
       ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Duplicate authoritative Pumper Dashboard payment source';
    END IF;
SQL : '';

        DB::unprepared(<<<SQL
CREATE TRIGGER trg_pop_authority_bi_20260723
BEFORE INSERT ON pump_operator_payments
FOR EACH ROW
BEGIN
    IF NEW.pump_operator_id IS NOT NULL
       AND NEW.pump_operator_id > 0
       AND LOWER(NEW.payment_type) IN ('cash','card','cards','cheque','cheques','credit','multiple_credit','other','shortage','excess')
       AND (NEW.shift_id IS NULL OR NEW.shift_id = 0) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pump Operator Payment requires an immutable Shift ID';
    END IF;{$sourceGuard}
END
SQL);
    }

    private function createMasterUpdateGuard(): void
    {
        if (! Schema::hasTable('pump_operator_payments')) {
            return;
        }

        $hasSourceColumns = Schema::hasColumn('pump_operator_payments', 'source_type')
            && Schema::hasColumn('pump_operator_payments', 'source_id');

        $sourceGuard = $hasSourceColumns ? <<<'SQL'

    IF OLD.source_type IS NOT NULL
       AND OLD.source_type <> ''
       AND OLD.source_id IS NOT NULL
       AND OLD.source_id > 0
       AND (NOT (NEW.source_type <=> OLD.source_type)
            OR NOT (NEW.source_id <=> OLD.source_id)) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pump Operator Payment source identity is immutable';
    END IF;

    IF NEW.source_type IS NOT NULL
       AND NEW.source_type <> ''
       AND NEW.source_id IS NOT NULL
       AND NEW.source_id > 0
       AND EXISTS (
            SELECT 1
              FROM pump_operator_payments existing_payment
             WHERE existing_payment.business_id = NEW.business_id
               AND existing_payment.source_type = NEW.source_type
               AND existing_payment.source_id = NEW.source_id
               AND existing_payment.id <> NEW.id
       ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Duplicate authoritative Pumper Dashboard payment source';
    END IF;
SQL : '';

        DB::unprepared(<<<SQL
CREATE TRIGGER trg_pop_authority_bu_20260723
BEFORE UPDATE ON pump_operator_payments
FOR EACH ROW
BEGIN
    IF OLD.business_id IS NOT NULL
       AND OLD.business_id > 0
       AND NEW.business_id <> OLD.business_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pump Operator Payment business is immutable';
    END IF;

    IF OLD.pump_operator_id IS NOT NULL
       AND OLD.pump_operator_id > 0
       AND NEW.pump_operator_id <> OLD.pump_operator_id THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pump Operator Payment operator is immutable';
    END IF;

    IF OLD.shift_id IS NOT NULL
       AND OLD.shift_id > 0
       AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Pump Operator Payment Shift ID is immutable';
    END IF;{$sourceGuard}
END
SQL);
    }

    private function createDetailTrigger(string $table, string $operatorColumn, string $operation): void
    {
        $suffix = strtolower($operation === 'INSERT' ? 'bi' : 'bu');
        $shortName = $this->shortTableName($table);
        $triggerName = "trg_{$shortName}_authority_{$suffix}_20260723";
        $idExclusion = $operation === 'UPDATE' ? 'AND existing_detail.id <> NEW.id' : '';
        $hasOperator = $operatorColumn !== '' && Schema::hasColumn($table, $operatorColumn);
        $quotedOperator = str_replace('`', '``', $operatorColumn);

        $operatorGuard = $hasOperator ? <<<SQL

        IF NEW.`{$quotedOperator}` IS NULL OR NEW.`{$quotedOperator}` = 0 THEN
            SET NEW.`{$quotedOperator}` = v_operator_id;
        ELSEIF NEW.`{$quotedOperator}` <> v_operator_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Payment detail operator does not match master payment';
        END IF;
SQL : '';

        $immutability = $operation === 'UPDATE' ? <<<'SQL'

    IF OLD.shift_id IS NOT NULL
       AND OLD.shift_id > 0
       AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Settlement payment Shift ID is immutable';
    END IF;

    IF OLD.pump_payment_id IS NOT NULL
       AND OLD.pump_payment_id > 0
       AND NOT (NEW.pump_payment_id <=> OLD.pump_payment_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Settlement payment master link is immutable';
    END IF;
SQL : '';

        DB::unprepared(<<<SQL
CREATE TRIGGER `{$triggerName}`
BEFORE {$operation} ON `{$table}`
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business_id BIGINT DEFAULT NULL;
    DECLARE v_operator_id BIGINT DEFAULT NULL;
    DECLARE v_shift_id BIGINT DEFAULT NULL;{$immutability}

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id > 0 THEN
        SELECT COUNT(*), MAX(business_id), MAX(pump_operator_id), MAX(shift_id)
          INTO v_found, v_business_id, v_operator_id, v_shift_id
          FROM pump_operator_payments
         WHERE id = NEW.pump_payment_id;

        IF v_found <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Invalid authoritative Pump Operator Payment link';
        END IF;

        IF NEW.business_id IS NULL OR NEW.business_id = 0 THEN
            SET NEW.business_id = v_business_id;
        ELSEIF NEW.business_id <> v_business_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Settlement payment business does not match master payment';
        END IF;{$operatorGuard}

        IF NEW.shift_id IS NULL OR NEW.shift_id = 0 THEN
            SET NEW.shift_id = v_shift_id;
        ELSEIF NEW.shift_id <> v_shift_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Settlement payment Shift ID does not match master payment';
        END IF;

        IF EXISTS (
            SELECT 1
              FROM `{$table}` existing_detail
             WHERE existing_detail.business_id = NEW.business_id
               AND existing_detail.pump_payment_id = NEW.pump_payment_id
               {$idExclusion}
        ) THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Duplicate settlement detail for Pump Operator Payment';
        END IF;
    END IF;
END
SQL);
    }

    private function dropTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pop_authority_bu_20260723');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pop_authority_bi_20260723');

        foreach (array_keys(self::AUTHORITY_TABLES) as $table) {
            $shortName = $this->shortTableName($table);
            DB::unprepared("DROP TRIGGER IF EXISTS `trg_{$shortName}_authority_bi_20260723`");
            DB::unprepared("DROP TRIGGER IF EXISTS `trg_{$shortName}_authority_bu_20260723`");
        }
    }

    private function shortTableName(string $table): string
    {
        return match ($table) {
            'settlement_cash_payments' => 'scp',
            'settlement_card_payments' => 'scardp',
            'settlement_cheque_payments' => 'schqp',
            'settlement_credit_sale_payments' => 'scrp',
            'settlement_shortage_payments' => 'sshp',
            'settlement_excess_payments' => 'sexp',
            'daily_collections' => 'dcol',
            'daily_cards' => 'dcard',
            'daily_cheque_payments' => 'dchq',
            'daily_vouchers' => 'dvch',
            default => substr(preg_replace('/[^a-z0-9]/i', '', $table), 0, 20),
        };
    }
};
