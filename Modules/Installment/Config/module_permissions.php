<?php

/*
 * MA-004 - page permissions for the Installment module.
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
 *   11 pages found in Installment.
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
    ['key' => 'installment_install', 'label' => 'Install', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_install_uninstall', 'label' => 'Install Uninstall', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_install_update', 'label' => 'Install Update', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_installments', 'label' => 'Installments', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_installments_for_home', 'label' => 'Installments For Home', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_business', 'label' => 'Business', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_getsystemdata', 'label' => 'Getsystemdata', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_getinstallment', 'label' => 'Getinstallment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_contacts', 'label' => 'Contacts', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_contactwithinstallment', 'label' => 'Contactwithinstallment', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'installment_sells', 'label' => 'Sells', 'type' => 'page', 'source' => 'module_pages'],
];
