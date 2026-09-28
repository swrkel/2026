<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customers Module Configuration
    |--------------------------------------------------------------------------
    */

    'name' => 'Customers',

    'version' => '1.0.0',

    'use_contacts_table' => true,

    'default_contact_type' => 'customer',

    'reports_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Standalone Cleanup Controls - CUS_FINAL_001
    |--------------------------------------------------------------------------
    |
    | keep_legacy_contact_permission_fallbacks is kept true by default so older
    | tenants continue working while new customers.* permissions are rolled out.
    | After role migration, set this to false to remove Contact permission fallback
    | behavior without changing code.
    |
    */

    'keep_legacy_contact_permission_fallbacks' => false,

    'standalone_readiness_target' => 99,

    /*
    |--------------------------------------------------------------------------
    | Customer Reference - Task 8046
    |--------------------------------------------------------------------------
    |
    | fuel_category_id
    |     Explicit id of the PRODUCT category whose sub-categories are offered
    |     as Fuel Types. Leave null to fall back to matching by name below.
    |     Set this on any tenant whose fuel category is not literally named
    |     "Fuel" - it is the only reliable way to identify it, because the
    |     categories table carries no flag saying which one is fuel.
    |
    | fuel_category_names
    |     Names matched case-insensitively when fuel_category_id is null.
    |
    | qr_driver
    |     Pin the QR library instead of auto-detecting. One of:
    |     simple-qrcode, endroid, milon-barcode, bacon. Leave null to detect.
    |
    | pdf_driver
    |     Pin the PDF writer instead of auto-detecting. One of:
    |     dompdf-facade, dompdf-facade-legacy, snappy. Leave null to detect.
    |
    | Both driver keys exist so a tenant can lock in the package it has
    | actually tested, rather than silently changing behaviour when an
    | unrelated composer update adds another candidate library.
    |
    */

    'fuel_category_id' => null,

    'fuel_category_names' => ['Fuel', 'Fuels', 'Fuel Products'],

    /*
     * Pinned to milon-barcode rather than left on auto-detect.
     *
     * milon/barcode is confirmed installed here - the legacy Vehicle No page
     * uses DNS2D with 'QRCODE' - so there is no reason to leave the choice to
     * runtime detection. Pinning also means an unrelated composer update that
     * adds another QR package cannot silently change which renderer this
     * feature uses.
     */
    'qr_driver' => 'milon-barcode',

    'pdf_driver' => 'dompdf-facade',

];
