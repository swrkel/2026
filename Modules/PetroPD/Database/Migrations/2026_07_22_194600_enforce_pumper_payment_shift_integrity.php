<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropTriggers();

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_pop_shift_immutable_bu
BEFORE UPDATE ON pump_operator_payments
FOR EACH ROW
BEGIN
    IF OLD.shift_id IS NOT NULL
       AND OLD.shift_id > 0
       AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'pump_operator_payments.shift_id is immutable';
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_scsp_master_scope_bi
BEFORE INSERT ON settlement_credit_sale_payments
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business_id BIGINT DEFAULT NULL;
    DECLARE v_operator_id BIGINT DEFAULT NULL;
    DECLARE v_shift_id BIGINT DEFAULT NULL;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id > 0 THEN
        SELECT COUNT(*), MAX(business_id), MAX(pump_operator_id), MAX(shift_id)
          INTO v_found, v_business_id, v_operator_id, v_shift_id
          FROM pump_operator_payments
         WHERE id = NEW.pump_payment_id;

        IF v_found <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Invalid master Pump Operator Payment link';
        END IF;

        IF NEW.business_id IS NULL OR NEW.business_id = 0 THEN
            SET NEW.business_id = v_business_id;
        ELSEIF NEW.business_id <> v_business_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale business_id does not match master payment';
        END IF;

        IF NEW.pump_operator_id IS NULL OR NEW.pump_operator_id = 0 THEN
            SET NEW.pump_operator_id = v_operator_id;
        ELSEIF NEW.pump_operator_id <> v_operator_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale pump_operator_id does not match master payment';
        END IF;

        IF NEW.shift_id IS NULL OR NEW.shift_id = 0 THEN
            SET NEW.shift_id = v_shift_id;
        ELSEIF NEW.shift_id <> v_shift_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale shift_id does not match master payment';
        END IF;
    END IF;
END
SQL);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER trg_scsp_master_scope_bu
BEFORE UPDATE ON settlement_credit_sale_payments
FOR EACH ROW
BEGIN
    DECLARE v_found INT DEFAULT 0;
    DECLARE v_business_id BIGINT DEFAULT NULL;
    DECLARE v_operator_id BIGINT DEFAULT NULL;
    DECLARE v_shift_id BIGINT DEFAULT NULL;

    IF OLD.shift_id IS NOT NULL
       AND OLD.shift_id > 0
       AND NOT (NEW.shift_id <=> OLD.shift_id) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'settlement_credit_sale_payments.shift_id is immutable';
    END IF;

    IF NEW.pump_payment_id IS NOT NULL AND NEW.pump_payment_id > 0 THEN
        SELECT COUNT(*), MAX(business_id), MAX(pump_operator_id), MAX(shift_id)
          INTO v_found, v_business_id, v_operator_id, v_shift_id
          FROM pump_operator_payments
         WHERE id = NEW.pump_payment_id;

        IF v_found <> 1 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Invalid master Pump Operator Payment link';
        END IF;

        IF NEW.business_id IS NULL OR NEW.business_id = 0 THEN
            SET NEW.business_id = v_business_id;
        ELSEIF NEW.business_id <> v_business_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale business_id does not match master payment';
        END IF;

        IF NEW.pump_operator_id IS NULL OR NEW.pump_operator_id = 0 THEN
            SET NEW.pump_operator_id = v_operator_id;
        ELSEIF NEW.pump_operator_id <> v_operator_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale pump_operator_id does not match master payment';
        END IF;

        IF NEW.shift_id IS NULL OR NEW.shift_id = 0 THEN
            SET NEW.shift_id = v_shift_id;
        ELSEIF NEW.shift_id <> v_shift_id THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Credit Sale shift_id does not match master payment';
        END IF;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        $this->dropTriggers();
    }

    private function dropTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_scsp_master_scope_bu');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_scsp_master_scope_bi');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_pop_shift_immutable_bu');
    }
};
