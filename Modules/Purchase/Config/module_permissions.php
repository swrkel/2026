<?php

/*
 * MA-004 - page permissions for the Purchase module.
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
 *   22 pages found in Purchase.
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
    ['key' => 'purchase_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_suppliers', 'label' => 'Suppliers', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_purchase_orders', 'label' => 'Purchase Orders', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_stores', 'label' => 'Stores', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_products', 'label' => 'Products', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_unload_tanks', 'label' => 'Unload Tanks', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_reference_check', 'label' => 'Reference Check', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_purchase_register', 'label' => 'Purchase Register', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_purchase_payment', 'label' => 'Purchase Payment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_product_purchase', 'label' => 'Product Purchase', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_purchase_sell', 'label' => 'Purchase Sell', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_stock_purchase_sale', 'label' => 'Stock Purchase Sale', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_supplier_outstanding', 'label' => 'Supplier Outstanding', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_widgets_summary', 'label' => 'Widgets Summary', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_widgets_outstanding', 'label' => 'Widgets Outstanding', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_widgets_recent_purchases', 'label' => 'Widgets Recent Purchases', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_numbering', 'label' => 'Numbering', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_approval', 'label' => 'Approval', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_tax', 'label' => 'Tax', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_supplier', 'label' => 'Supplier', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_general', 'label' => 'General', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'purchase_purchases', 'label' => 'Purchases', 'type' => 'page', 'source' => 'module_pages'],
];
