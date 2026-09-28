<?php

/*
 * MA-004 - page permissions for the MyHealth module.
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
 *   8 pages found in MyHealth.
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
    ['key' => 'myhealth_patient_getpatient', 'label' => 'Patient Getpatient', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_patient_sugar_reading', 'label' => 'Patient Sugar Reading', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_patient_sugar_reading_fetchdata', 'label' => 'Patient Sugar Reading Fetchdata', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_patient_sugar_readings', 'label' => 'Patient Sugar Readings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_medication', 'label' => 'Medication', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_medication_create', 'label' => 'Medication Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_medication_store', 'label' => 'Medication Store', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'myhealth_image_modal', 'label' => 'Image Modal', 'type' => 'page', 'source' => 'module_pages'],
];
