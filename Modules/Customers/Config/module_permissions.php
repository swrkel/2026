<?php

/*
 * MA-004 - page permissions for the Customers module.
 *
 * WHY THIS FILE EXISTS
 *   The Role and Permissions screen builds its checkbox list from each
 *   module's Config/module_permissions.php. Only SEVEN of the 119 modules
 *   had one, so a Business Admin could set page permissions for seven
 *   modules and saw nothing for the other 112 - which is what MA-004
 *   reports.
 *
 *   These entries were derived from this module's own GET routes: one page
 *   per navigable url. Endpoints that are not pages - anything ending in
 *   /data, /options, /export, /get-something, or carrying a {parameter} -
 *   were left out, because they are ajax calls rather than screens a
 *   permission would sensibly guard.
 *
 *   76 pages found in Customers.
 *
 * EDITING THIS FILE
 *   It is ordinary configuration, safe to edit by hand. Change a label to
 *   whatever reads better on the permissions screen, or delete a line to
 *   remove a page from it. Nothing regenerates this automatically.
 *
 *   Modules that already had a hand-written module_permissions.php were
 *   NOT touched - PetroPDNew, PumperDashboardNew, StockTakingNew,
 *   PetroDirectNew, RestaurantNew, UserManagementNew and ManagementReport
 *   keep their curated lists.
 */

return [
    ['key' => 'customers_dashboard', 'label' => 'Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_payment_accounts_by_method', 'label' => 'Payment Accounts By Method', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_register', 'label' => 'Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_register_total_due', 'label' => 'Register Total Due', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_list', 'label' => 'Customer List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_ledger', 'label' => 'Customer Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_statement', 'label' => 'Customer Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_aging', 'label' => 'Customer Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_inactive_customers', 'label' => 'Inactive Customers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_balance', 'label' => 'Customer Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_transactions', 'label' => 'Customer Transactions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_payments', 'label' => 'Customer Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_numbering', 'label' => 'Numbering', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_payment_reference_settings', 'label' => 'Payment Reference Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_defaults', 'label' => 'Defaults', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_preferences', 'label' => 'Preferences', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_portal', 'label' => 'Portal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_credit', 'label' => 'Credit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_notifications', 'label' => 'Notifications', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_import', 'label' => 'Import', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_import_balance', 'label' => 'Import Balance', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_approvals', 'label' => 'Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_approvals_create', 'label' => 'Approvals Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_credit_approvals', 'label' => 'Credit Approvals', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_history', 'label' => 'History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_approval_audit', 'label' => 'Approval Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_references', 'label' => 'List Customer Reference', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_standalone_audit', 'label' => 'Standalone Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_interest', 'label' => 'Customer Interest', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_bulk_payment', 'label' => 'Bulk Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_bulk_payment_runtime_script', 'label' => 'Bulk Payment Runtime Script', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_payment_bulk', 'label' => 'Customer Payment Bulk', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_issued_payment_details', 'label' => 'Issued Payment Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_returned_cheque_details', 'label' => 'Returned Cheque Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_outstanding_received_report', 'label' => 'Outstanding Received Report', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_outstanding_received_report_filters', 'label' => 'Outstanding Received Report Filters', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_statements_pmts', 'label' => 'Customer Statement - Pymts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_statement_list_payments', 'label' => 'Customer Statement List Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_customer_statement_user_activity', 'label' => 'Customer Statement User Activity', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_pay_due', 'label' => 'Pay Due', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_advance_payment', 'label' => 'Advance Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_loan', 'label' => 'Loan', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_refund_deposit', 'label' => 'Refund Deposit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_refund_payment', 'label' => 'Refund Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_cheque_return', 'label' => 'Cheque Return', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_security_deposit', 'label' => 'Security Deposit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_ledger', 'label' => 'Ledger', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_statement', 'label' => 'Statement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_balance_details', 'label' => 'Balance Details', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_contact_info', 'label' => 'Contact Info', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_documents', 'label' => 'Documents', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_audit', 'label' => 'Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_notes', 'label' => 'Notes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_activity', 'label' => 'Activity', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_login', 'label' => 'Login', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_invoices', 'label' => 'Invoices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_payments', 'label' => 'Payments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_orders', 'label' => 'Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_outstanding', 'label' => 'Outstanding', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_profile', 'label' => 'Profile', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_announcements', 'label' => 'Announcements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_messages', 'label' => 'Messages', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_analytics', 'label' => 'Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_assistant', 'label' => 'Assistant', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_business_health', 'label' => 'Business Health', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_deliveries', 'label' => 'Deliveries', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_live_tracking', 'label' => 'Live Tracking', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_place_order', 'label' => 'Place Order', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_order_favourites', 'label' => 'Order Favourites', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_logout', 'label' => 'Logout', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_me', 'label' => 'Me', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_credit_summary', 'label' => 'Credit Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_statements', 'label' => 'Statements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_tracking', 'label' => 'Tracking', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_vehicle_location', 'label' => 'Vehicle Location', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_loyalty', 'label' => 'Loyalty', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'customers_rewards', 'label' => 'Rewards', 'type' => 'page', 'source' => 'module_pages'],
];
