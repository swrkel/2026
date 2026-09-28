CREATE OR REPLACE VIEW `atn_v_ticket_financial_summary` AS
SELECT t.business_id,t.business_location_id,t.store_id,t.id ticket_id,t.ticket_no,t.issue_date,t.currency_code,t.grand_total sale_amount,COALESCE(p.net_profit,0) net_profit
FROM atn_tickets t LEFT JOIN atn_ticket_profits p ON p.business_id=t.business_id AND p.ticket_id=t.id;
