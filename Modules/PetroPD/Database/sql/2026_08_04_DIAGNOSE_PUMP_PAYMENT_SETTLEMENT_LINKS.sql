-- PETRO PD: READ-ONLY PAYMENT/SETTLEMENT LINK DIAGNOSTIC
-- Select the affected tenant database first.
-- Change only the settlement code below when diagnosing another settlement.

SET @target_settlement_no := 'PDST180';
SET @target_settlement_id := (
    SELECT id
    FROM settlements
    WHERE settlement_no = @target_settlement_no
    ORDER BY id DESC
    LIMIT 1
);
SET @target_business_id := (
    SELECT business_id
    FROM settlements
    WHERE id = @target_settlement_id
    LIMIT 1
);
SET @target_shift_id := 181;

SELECT
    linked_rows.payment_table,
    linked_rows.detail_id,
    linked_rows.pump_payment_id,
    linked_rows.detail_settlement_no,
    linked_rows.master_settlement_no,
    linked_rows.payment_type,
    linked_rows.shift_id,
    CASE
        WHEN CAST(linked_rows.detail_settlement_no AS CHAR) = CAST(@target_settlement_id AS CHAR)
          OR CAST(linked_rows.detail_settlement_no AS CHAR) = @target_settlement_no
        THEN 'CURRENT SETTLEMENT REPRESENTATION'
        ELSE 'DIFFERENT SETTLEMENT / CONFLICT'
    END AS link_status
FROM (
    SELECT 'settlement_cash_payments' AS payment_table, d.id AS detail_id,
           d.pump_payment_id, d.settlement_no AS detail_settlement_no,
           p.settlement_no AS master_settlement_no, p.payment_type, p.shift_id
    FROM settlement_cash_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id

    UNION ALL
    SELECT 'settlement_card_payments', d.id, d.pump_payment_id, d.settlement_no,
           p.settlement_no, p.payment_type, p.shift_id
    FROM settlement_card_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id

    UNION ALL
    SELECT 'settlement_cheque_payments', d.id, d.pump_payment_id, d.settlement_no,
           p.settlement_no, p.payment_type, p.shift_id
    FROM settlement_cheque_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id

    UNION ALL
    SELECT 'settlement_credit_sale_payments', d.id, d.pump_payment_id, d.settlement_no,
           p.settlement_no, p.payment_type, p.shift_id
    FROM settlement_credit_sale_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id

    UNION ALL
    SELECT 'settlement_shortage_payments', d.id, d.pump_payment_id, d.settlement_no,
           p.settlement_no, p.payment_type, p.shift_id
    FROM settlement_shortage_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id

    UNION ALL
    SELECT 'settlement_excess_payments', d.id, d.pump_payment_id, d.settlement_no,
           p.settlement_no, p.payment_type, p.shift_id
    FROM settlement_excess_payments d
    INNER JOIN pump_operator_payments p ON p.id = d.pump_payment_id
    WHERE d.business_id = @target_business_id AND p.shift_id = @target_shift_id
) AS linked_rows
ORDER BY linked_rows.pump_payment_id, linked_rows.payment_table;
