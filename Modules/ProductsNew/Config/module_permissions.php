<?php

/*
 * MA-004 - page permissions for the ProductsNew module.
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
 *   70 pages found in ProductsNew.
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
    ['key' => 'products_pumper_dashboard_default', 'label' => 'Show in Pumper Dashboard', 'type' => 'permission', 'source' => 'legacy_manage', 'default_enabled' => false],
    ['key' => 'productsnew_product_master', 'label' => 'Product Master', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_stock_valuation', 'label' => 'Stock Valuation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_low_stock', 'label' => 'Low Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_price_changes', 'label' => 'Price Changes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_batch_lot', 'label' => 'Batch Lot', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_product_movement', 'label' => 'Product Movement', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_profitability', 'label' => 'Profitability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_aging', 'label' => 'Aging', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_fast_slow_dead_stock', 'label' => 'Fast Slow Dead Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_negative_overstock', 'label' => 'Negative Overstock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_expiry', 'label' => 'Expiry', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_serial', 'label' => 'Serial', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_category_brand', 'label' => 'Category Brand', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_price_history_analytics', 'label' => 'Price History Analytics', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_turnover', 'label' => 'Inventory Turnover', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_abc_xyz', 'label' => 'Abc Xyz', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_reorder_recommendation', 'label' => 'Reorder Recommendation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_kpi_centre', 'label' => 'Kpi Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_stock_center', 'label' => 'Stock Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_movements', 'label' => 'Inventory Movements', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_stock_history', 'label' => 'Stock History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_opening_stock', 'label' => 'Opening Stock', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_opening_stock_import_template', 'label' => 'Opening Stock Import Template', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_price_center', 'label' => 'Price Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_barcode_center', 'label' => 'Barcode Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_barcode_center_templates', 'label' => 'Barcode Center Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_media_center', 'label' => 'Media Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence', 'label' => 'Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence_relationships', 'label' => 'Intelligence Relationships', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence_workflow', 'label' => 'Intelligence Workflow', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence_duplicates', 'label' => 'Intelligence Duplicates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence_notes', 'label' => 'Intelligence Notes', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_intelligence_availability', 'label' => 'Intelligence Availability', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_batch_centre', 'label' => 'Batch Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_expiry_centre', 'label' => 'Expiry Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_recall_centre', 'label' => 'Recall Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_serial_centre', 'label' => 'Serial Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_warranty_centre', 'label' => 'Warranty Centre', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_ownership_history', 'label' => 'Ownership History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_import_export', 'label' => 'Import Export', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_import_export_template', 'label' => 'Import Export Template', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_data_cleanup', 'label' => 'Data Cleanup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_production_audit', 'label' => 'Production Audit', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_integration_bridge', 'label' => 'Integration Bridge', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_migration_readiness', 'label' => 'Migration Readiness', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_legacy_comparison', 'label' => 'Legacy Comparison', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_testing_checklist', 'label' => 'Testing Checklist', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_deployment_readiness', 'label' => 'Deployment Readiness', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_product_types', 'label' => 'Framework Product Types', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_custom_fields', 'label' => 'Framework Custom Fields', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_rules', 'label' => 'Framework Rules', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_templates', 'label' => 'Framework Templates', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_saved_filters', 'label' => 'Framework Saved Filters', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_bulk_operations', 'label' => 'Framework Bulk Operations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_exceptions', 'label' => 'Framework Exceptions', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_framework_version_history', 'label' => 'Framework Version History', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_intelligence_planning', 'label' => 'Inventory Intelligence Planning', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_intelligence_cost_analysis', 'label' => 'Inventory Intelligence Cost Analysis', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_intelligence_stock_intelligence', 'label' => 'Inventory Intelligence Stock Intelligence', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_intelligence_abc_xyz', 'label' => 'Inventory Intelligence Abc Xyz', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_inventory_intelligence_executive', 'label' => 'Inventory Intelligence Executive', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_command_center', 'label' => 'Command Center', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_settings', 'label' => 'Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_categories', 'label' => 'Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_settings_categories', 'label' => 'Settings Categories', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_settings_brands', 'label' => 'Settings Brands', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_settings_units', 'label' => 'Settings Units', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_settings_variations', 'label' => 'Settings Variations', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_lookup', 'label' => 'Lookup', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'productsnew_integration_lookup', 'label' => 'Integration Lookup', 'type' => 'page', 'source' => 'module_pages'],
];
