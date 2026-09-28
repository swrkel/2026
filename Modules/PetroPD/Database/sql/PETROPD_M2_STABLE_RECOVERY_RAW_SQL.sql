-- PETROPD_M2_STABLE_RECOVERY_RAW_SQL.sql
-- Optional safety indexes for Petro PD Milestone 2.
-- Run only if the indexes do not already exist in each tenant database.
-- These indexes support Payment Summary, settlement lookup, duplicate prevention checks,
-- and tenant-safe shift/payment filtering.

ALTER TABLE pump_operator_payments
  ADD INDEX idx_pd_pop_business_shift_operator (business_id, shift_id, pump_operator_id),
  ADD INDEX idx_pd_pop_business_type_date (business_id, payment_type, date_and_time),
  ADD INDEX idx_pd_pop_settlement_no (settlement_no);

ALTER TABLE pump_operator_assignments
  ADD INDEX idx_pd_poa_business_shift_operator (business_id, shift_id, pump_operator_id),
  ADD INDEX idx_pd_poa_status_closed (business_id, status, is_manually_closed);

ALTER TABLE settlements
  ADD INDEX idx_pd_settlements_business_operator_status (business_id, pump_operator_id, status),
  ADD INDEX idx_pd_settlements_no_business (settlement_no, business_id);

ALTER TABLE settlement_credit_sale_payments
  ADD INDEX idx_pd_scsp_business_pump_payment (business_id, pump_payment_id),
  ADD INDEX idx_pd_scsp_settlement_no (settlement_no);

ALTER TABLE daily_cards
  ADD INDEX idx_pd_daily_cards_match (business_id, collection_no, amount);
