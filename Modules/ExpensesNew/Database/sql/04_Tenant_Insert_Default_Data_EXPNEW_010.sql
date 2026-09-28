INSERT INTO expnew_financial_signals (business_id, location_id, signal_type, severity, title, message, status, created_at, updated_at)
SELECT 0, 0, 'system_ready', 'info', 'Financial Intelligence Ready', 'EXPNEW_010 financial intelligence parcel installed.', 'open', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_financial_signals WHERE signal_type='system_ready' AND title='Financial Intelligence Ready');
