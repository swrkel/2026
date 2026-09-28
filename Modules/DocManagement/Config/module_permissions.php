<?php

/*
 * MA-004 - page permissions for the DocManagement module.
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
 *   25 pages found in DocManagement.
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
    ['key' => 'docmanagement_documet', 'label' => 'Documet', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_doc_settings', 'label' => 'Doc Settings', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_category_gets', 'label' => 'Document Category Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_department_gets', 'label' => 'Document Department Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_designation_gets', 'label' => 'Document Designation Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_type_gets', 'label' => 'Document Type Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_purpose_gets', 'label' => 'Document Purpose Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_forwardwith_gets', 'label' => 'Document Forwardwith Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_status_gets', 'label' => 'Document Status Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_mandatorysignature_gets', 'label' => 'Document Mandatorysignature Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_upload_gets', 'label' => 'Document Upload Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_uploadlogo_gets', 'label' => 'Document Uploadlogo Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_referred_to_gets', 'label' => 'Document Referred To Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_referred_to_status_history_gets', 'label' => 'Document Referred To Status History Gets', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_document_designations_by_department', 'label' => 'Document Designations By Department', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_mandatorysignature', 'label' => 'Store Mandatorysignature', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_forwardwith', 'label' => 'Store Forwardwith', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_doc_status', 'label' => 'Store Doc Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_update_referred_to_status', 'label' => 'Update Referred To Status', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_category_type', 'label' => 'Store Category Type', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_department', 'label' => 'Store Department', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_store_designation', 'label' => 'Store Designation', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_show', 'label' => 'Show', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_create', 'label' => 'Create', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'docmanagement_get_upload_table', 'label' => 'Get Upload Table', 'type' => 'page', 'source' => 'module_pages'],
];
