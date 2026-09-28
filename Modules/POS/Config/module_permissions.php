<?php

/*
 * MA-004 - page permissions for the POS module.
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
 *   65 pages found in POS.
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
    ['key' => 'pos_returns', 'label' => 'Returns', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_advanced_sales', 'label' => 'Advanced Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_kitchen', 'label' => 'Kitchen', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_shifts', 'label' => 'Shifts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_configuration_center', 'label' => 'Configuration Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_shifts_current', 'label' => 'Shifts Current', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_shifts_open', 'label' => 'Shifts Open', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales', 'label' => 'Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_workspace', 'label' => 'Sales Workspace', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_workspace_products', 'label' => 'Sales Workspace Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_workspace_customers', 'label' => 'Sales Workspace Customers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_workspace_price_check', 'label' => 'Sales Workspace Price Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_list', 'label' => 'Sales List', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_search_products', 'label' => 'Sales Search Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_returns_create', 'label' => 'Returns Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_exchanges', 'label' => 'Exchanges', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_exchanges_create', 'label' => 'Exchanges Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_cart', 'label' => 'Sales Cart', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_inventory_setup', 'label' => 'Inventory Setup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_barcodes', 'label' => 'Barcodes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_stock_adjustment', 'label' => 'Stock Adjustment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_stock_movements', 'label' => 'Stock Movements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_receipt_designer', 'label' => 'Settings Receipt Designer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_barcode_designer', 'label' => 'Settings Barcode Designer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_terminal', 'label' => 'Settings Terminal', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_hardware', 'label' => 'Settings Hardware', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_security', 'label' => 'Settings Security', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_settings_number_series', 'label' => 'Settings Number Series', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_standalone_audit', 'label' => 'Standalone Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_live_testing_support', 'label' => 'Live Testing Support', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_production_stabilization', 'label' => 'Production Stabilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_production_readiness', 'label' => 'Production Readiness', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_post_live_stabilization', 'label' => 'Post Live Stabilization', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync', 'label' => 'Offline Sync', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_manifest', 'label' => 'Offline Sync Manifest', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_pending', 'label' => 'Offline Sync Pending', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_offline_sales', 'label' => 'Offline Sync Offline Sales', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_cache_products', 'label' => 'Offline Sync Cache Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_cache_customers', 'label' => 'Offline Sync Cache Customers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_cache_settings', 'label' => 'Offline Sync Cache Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_cache_stock', 'label' => 'Offline Sync Cache Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_cache_all', 'label' => 'Offline Sync Cache All', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_conflicts', 'label' => 'Offline Sync Conflicts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_conflict_manager', 'label' => 'Offline Sync Conflict Manager', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_background_engine', 'label' => 'Offline Sync Background Engine', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_heartbeat', 'label' => 'Offline Sync Heartbeat', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_progress', 'label' => 'Offline Sync Progress', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_monitoring_center', 'label' => 'Offline Sync Monitoring Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_offline_sync_devices', 'label' => 'Offline Sync Devices', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_cash_drawer', 'label' => 'Cash Drawer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_cash_drawer_cash_in', 'label' => 'Cash Drawer Cash In', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_cash_drawer_cash_out', 'label' => 'Cash Drawer Cash Out', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_cash_drawer_count', 'label' => 'Cash Drawer Count', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_reports', 'label' => 'Reports', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_plugins', 'label' => 'Plugins', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_discounts', 'label' => 'Discounts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_loyalty', 'label' => 'Loyalty', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_receipts_designer', 'label' => 'Receipts Designer', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_enterprise_monitor', 'label' => 'Enterprise Monitor', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_price_check', 'label' => 'Sales Price Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_calculator', 'label' => 'Sales Calculator', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_held', 'label' => 'Sales Held', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_suspended', 'label' => 'Sales Suspended', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'pos_sales_quotations', 'label' => 'Sales Quotations', 'type' => 'page', 'source' => 'module_pages'],
];
