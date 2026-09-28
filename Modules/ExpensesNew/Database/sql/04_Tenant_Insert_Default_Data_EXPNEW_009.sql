-- EXPNEW_009 Default widgets - safe re-run
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'today_expenses', 'Today Expenses', 'kpi', 10, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='today_expenses');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_approvals', 'Pending Approvals', 'kpi', 20, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_approvals');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'pending_payments', 'Pending Payments', 'kpi', 30, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='pending_payments');
INSERT INTO expnew_command_widgets (business_id, widget_key, widget_title, widget_type, sort_order, is_active, created_at, updated_at)
SELECT NULL, 'budget_alerts', 'Budget Alerts', 'alert', 40, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM expnew_command_widgets WHERE business_id IS NULL AND widget_key='budget_alerts');
