CREATE OR REPLACE VIEW `san_v_stock_adjustment_summary` AS
SELECT a.business_id, a.location_id, a.store_id, a.status, DATE(a.adjustment_date) AS adjustment_date,
       COUNT(*) AS adjustment_count, SUM(a.total_qty) AS total_qty, SUM(a.total_cost_amount) AS total_cost_amount
FROM san_stock_adjustments a
GROUP BY a.business_id, a.location_id, a.store_id, a.status, DATE(a.adjustment_date);
