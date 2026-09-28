<?php

use Illuminate\Support\Facades\Route;
use Modules\Suppliers\Http\Controllers\SupplierController;
use Modules\Suppliers\Http\Controllers\SupplierDashboardController;
use Modules\Suppliers\Http\Controllers\SupplierProfileTabController;
use Modules\Suppliers\Http\Controllers\SupplierContactTabController;
use Modules\Suppliers\Http\Controllers\SupplierDocumentTabController;
use Modules\Suppliers\Http\Controllers\SupplierAccountTabController;
use Modules\Suppliers\Http\Controllers\SupplierPurchaseHistoryTabController;
use Modules\Suppliers\Http\Controllers\SupplierLedgerTabController;
use Modules\Suppliers\Http\Controllers\SupplierPaymentTabController;
use Modules\Suppliers\Http\Controllers\SupplierMappingTabController;
use Modules\Suppliers\Http\Controllers\SupplierStockReportTabController;
use Modules\Suppliers\Http\Controllers\SupplierImportTabController;
use Modules\Suppliers\Http\Controllers\SupplierReportController;
use Modules\Suppliers\Http\Controllers\SupplierIssuePaymentDetailsController;
use Modules\Suppliers\Http\Controllers\SupplierUserActivityController;
use Modules\Suppliers\Http\Controllers\SupplierExportController;
use Modules\Suppliers\Http\Controllers\SupplierFinancialTabController;
use Modules\Suppliers\Http\Controllers\SupplierPaymentReferenceSettingsController;
use Modules\Suppliers\Http\Controllers\SupplierNotesTabController;
use Modules\Suppliers\Http\Controllers\SupplierAuditTabController;
use Modules\Suppliers\Http\Controllers\SupplierLookupController;
use Modules\Suppliers\Http\Middleware\EnsureSuppliersModuleAccess;
use Modules\Suppliers\Http\Controllers\Ledger\SupplierStatementController;
use Modules\Suppliers\Http\Controllers\Ledger\SupplierAgingController;
use Modules\Suppliers\Http\Controllers\Ledger\SupplierBalanceSummaryController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierContactPersonController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierDocumentController as SupplierCommunicationDocumentController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierNoteController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierCommunicationLogController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierEmailHistoryController;
use Modules\Suppliers\Http\Controllers\Communication\SupplierCommunicationAuditController;


// This file may be reached by both the module provider and the tenant fallback.
// Guard by the actual canonical GET URI rather than only the route name: a stale
// named route can exist in copied/cache-heavy deployments without GET /suppliers
// being present in the active route collection.
$__suppliersCanonicalGetExists = false;
foreach (Route::getRoutes() as $__suppliersRoute) {
    if (trim((string) $__suppliersRoute->uri(), '/') === 'suppliers'
        && in_array('GET', $__suppliersRoute->methods(), true)) {
        $__suppliersCanonicalGetExists = true;
        break;
    }
}
unset($__suppliersRoute);

if ($__suppliersCanonicalGetExists) {
    unset($__suppliersCanonicalGetExists);
    return;
}
unset($__suppliersCanonicalGetExists);

Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone', 'tenant.context', EnsureSuppliersModuleAccess::class])
    ->prefix('suppliers')
    ->as('suppliers.')
    ->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('records.index');
        Route::get('/data', [SupplierController::class, 'data'])->name('records.data');
        Route::get('/lookup/suppliers', [SupplierLookupController::class, 'suppliers'])->name('lookup.suppliers');
        Route::get('/lookup/products', [SupplierLookupController::class, 'products'])->name('lookup.products');
        Route::get('/export/{format}', [SupplierExportController::class, '__invoke'])->whereIn('format', ['csv', 'excel', 'pdf'])->name('records.export');
        Route::get('/dashboard', [SupplierDashboardController::class, 'index'])->name('dashboard');

        // S381: Compatibility aliases used by older sidebar/package records.
        // Keep these inside the Suppliers module so the standalone module does not rely on Contact routes.
        Route::get('/list-suppliers', [SupplierController::class, 'index'])->name('legacy.list_suppliers');
        Route::get('/all-suppliers', [SupplierController::class, 'index'])->name('legacy.all_suppliers');
        Route::get('/add-supplier', [SupplierController::class, 'create'])->name('legacy.add_supplier');
        Route::get('/supplier-payments', [SupplierPaymentTabController::class, 'index'])->name('legacy.supplier_payments');
        Route::get('/supplier-product-mapping', [SupplierMappingTabController::class, 'index'])->name('legacy.supplier_product_mapping');
        Route::get('/supplier-stock-report', [SupplierStockReportTabController::class, 'index'])->name('legacy.supplier_stock_report');
        Route::get('/import-suppliers', [SupplierImportTabController::class, 'index'])->name('legacy.import_suppliers');
        Route::get('/issues-payment-details', [SupplierIssuePaymentDetailsController::class, 'index'])->name('legacy.issues_payment_details');
        Route::get('/reports', [SupplierReportController::class, 'index'])->name('legacy.reports');

        // S409 (7 Jul 2026): URL compatibility aliases for Suppliers module menu records.
        // Some existing sidebars/package records use underscores, singular names, or old Contact-module labels.
        // Keep all aliases inside this standalone module and route them to the module pages, not to Contacts.
        Route::get('/list_supplier', [SupplierController::class, 'index'])->name('legacy.list_supplier_underscore');
        Route::get('/list-supplier', [SupplierController::class, 'index'])->name('legacy.list_supplier_singular');
        Route::get('/list', [SupplierController::class, 'index'])->name('legacy.list');
        Route::get('/add_supplier', [SupplierController::class, 'create'])->name('legacy.add_supplier_underscore');
        Route::get('/add', [SupplierController::class, 'create'])->name('legacy.add');
        Route::get('/supplier_payments', [SupplierPaymentTabController::class, 'index'])->name('legacy.supplier_payments_underscore');
        Route::get('/payments', [SupplierPaymentTabController::class, 'index'])->name('legacy.payments');
        Route::get('/supplier_product_mapping', [SupplierMappingTabController::class, 'index'])->name('legacy.supplier_product_mapping_underscore');
        Route::get('/product-mapping', [SupplierMappingTabController::class, 'index'])->name('legacy.product_mapping');
        Route::get('/product_mapping', [SupplierMappingTabController::class, 'index'])->name('legacy.product_mapping_underscore');
        Route::get('/add-supplier-map-products', [SupplierMappingTabController::class, 'index'])->name('legacy.add_supplier_map_products');
        Route::get('/list-supplier-map-products', [SupplierMappingTabController::class, 'index'])->name('legacy.list_supplier_map_products');
        Route::get('/supplier_stock_report', [SupplierStockReportTabController::class, 'index'])->name('legacy.supplier_stock_report_underscore');
        Route::get('/stock-report', [SupplierStockReportTabController::class, 'index'])->name('legacy.stock_report');
        Route::get('/stock_report', [SupplierStockReportTabController::class, 'index'])->name('legacy.stock_report_underscore');
        Route::get('/import_suppliers', [SupplierImportTabController::class, 'index'])->name('legacy.import_suppliers_underscore');
        Route::get('/import', [SupplierImportTabController::class, 'index'])->name('legacy.import');
        Route::get('/report', [SupplierReportController::class, 'index'])->name('legacy.report');
        Route::get('/issued-payment-details', [SupplierIssuePaymentDetailsController::class, 'index'])->name('legacy.issued_payment_details');
        Route::get('/issued_payment_details', [SupplierIssuePaymentDetailsController::class, 'index'])->name('legacy.issued_payment_details_underscore');
        Route::get('/contact-user-activity', [SupplierUserActivityController::class, 'index'])->name('legacy.contact_user_activity');
        Route::get('/contact_user_activity', [SupplierUserActivityController::class, 'index'])->name('legacy.contact_user_activity_underscore');


        // S710 - Supplier Payment Reference Number settings.
        Route::get('/settings', [SupplierPaymentReferenceSettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/payment-references', [SupplierPaymentReferenceSettingsController::class, 'index'])->name('settings.payment_references.index');
        Route::post('/settings/payment-references', [SupplierPaymentReferenceSettingsController::class, 'store'])->name('settings.payment_references.store');
        Route::get('/settings/payment-references/{id}/edit', [SupplierPaymentReferenceSettingsController::class, 'edit'])->whereNumber('id')->name('settings.payment_references.edit');
        Route::put('/settings/payment-references/{id}', [SupplierPaymentReferenceSettingsController::class, 'update'])->whereNumber('id')->name('settings.payment_references.update');
        Route::delete('/settings/payment-references/{id}', [SupplierPaymentReferenceSettingsController::class, 'destroy'])->whereNumber('id')->name('settings.payment_references.destroy');

        Route::get('/create', [SupplierController::class, 'create'])->name('records.create');
        Route::post('/', [SupplierController::class, 'store'])->name('records.store');
        Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('records.edit')->whereNumber('supplier');
        Route::put('/{supplier}', [SupplierController::class, 'update'])->name('records.update')->whereNumber('supplier');
        Route::patch('/{supplier}/toggle-active', [SupplierController::class, 'toggleActive'])->name('records.toggle_active')->whereNumber('supplier');
        Route::get('/{supplier}/pay-due', [SupplierController::class, 'preparePayDue'])->name('records.prepare_pay_due')->whereNumber('supplier');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('records.destroy')->whereNumber('supplier');
        Route::get('/{supplier}', [SupplierController::class, 'show'])->name('records.show')->whereNumber('supplier');

        Route::get('/{supplier}/profile', [SupplierProfileTabController::class, 'index'])->name('profile.index')->whereNumber('supplier');
        Route::put('/{supplier}/profile', [SupplierProfileTabController::class, 'update'])->name('profile.update')->whereNumber('supplier');
        Route::get('/{supplier}/profile/financial', [SupplierFinancialTabController::class, 'index'])->name('profile.financial')->whereNumber('supplier');
        Route::get('/{supplier}/profile/notes', [SupplierNotesTabController::class, 'index'])->name('profile.notes')->whereNumber('supplier');
        Route::get('/{supplier}/profile/audit-trail', [SupplierAuditTabController::class, 'index'])->name('profile.audit')->whereNumber('supplier');
        Route::get('/{supplier}/contacts', [SupplierContactTabController::class, 'index'])->name('contacts.index')->whereNumber('supplier');
        Route::post('/{supplier}/contacts', [SupplierContactTabController::class, 'store'])->name('contacts.store')->whereNumber('supplier');
        Route::get('/{supplier}/documents', [SupplierDocumentTabController::class, 'index'])->name('documents.index')->whereNumber('supplier');
        Route::post('/{supplier}/documents', [SupplierDocumentTabController::class, 'store'])->name('documents.store')->whereNumber('supplier');
        Route::get('/{supplier}/accounts', [SupplierAccountTabController::class, 'index'])->name('accounts.index')->whereNumber('supplier');
        Route::get('/{supplier}/purchase-history', [SupplierPurchaseHistoryTabController::class, 'index'])->name('purchase_history.index')->whereNumber('supplier');

        Route::get('/{supplier}/ledger', [SupplierLedgerTabController::class, 'index'])->name('ledger.index')->whereNumber('supplier');
        Route::get('/{supplier}/ledger/data', [SupplierLedgerTabController::class, 'data'])->name('ledger.data')->whereNumber('supplier');

        Route::get('/{supplier}/statement', [SupplierStatementController::class, 'index'])->name('statement.index')->whereNumber('supplier');
        Route::get('/{supplier}/statement/data', [SupplierStatementController::class, 'data'])->name('statement.data')->whereNumber('supplier');
        Route::get('/{supplier}/aging', [SupplierAgingController::class, 'index'])->name('aging.index')->whereNumber('supplier');
        Route::get('/{supplier}/aging/data', [SupplierAgingController::class, 'data'])->name('aging.data')->whereNumber('supplier');
        Route::get('/{supplier}/balance-summary', [SupplierBalanceSummaryController::class, 'index'])->name('balance_summary.index')->whereNumber('supplier');
        Route::get('/{supplier}/balance-summary/data', [SupplierBalanceSummaryController::class, 'data'])->name('balance_summary.data')->whereNumber('supplier');

        Route::get('/payments/list', [SupplierPaymentTabController::class, 'index'])->name('payments.index');
        Route::get('/payments/data', [SupplierPaymentTabController::class, 'data'])->name('payments.data');
        Route::get('/payments/{payment}/view', [SupplierPaymentTabController::class, 'show'])
            ->whereNumber('payment')
            ->name('payments.show');
        Route::get('/payments/{payment}/edit', [SupplierPaymentTabController::class, 'edit'])
            ->whereNumber('payment')
            ->name('payments.edit');
        Route::put('/payments/{payment}', [SupplierPaymentTabController::class, 'update'])
            ->whereNumber('payment')
            ->name('payments.update');

        Route::get('/product-mappings/list', [SupplierMappingTabController::class, 'index'])->name('mappings.index');
        Route::post('/product-mappings', [SupplierMappingTabController::class, 'store'])->name('mappings.store');
        Route::delete('/product-mappings/{mapping}', [SupplierMappingTabController::class, 'destroy'])->name('mappings.destroy');

        Route::get('/stock-report/list/{supplier?}', [SupplierStockReportTabController::class, 'index'])->name('stock_report.index');
        Route::get('/stock-report/data/{supplier}', [SupplierStockReportTabController::class, 'data'])->name('stock_report.data');

        Route::get('/imports/contacts', [SupplierImportTabController::class, 'index'])->name('imports.index');
        Route::post('/imports/contacts', [SupplierImportTabController::class, 'postImport'])->name('imports.store');
        Route::get('/imports/opening-balance', [SupplierImportTabController::class, 'openingBalance'])->name('imports.opening_balance');


        // SUP-009: Supplier Contacts, Documents & Communication Center
        Route::get('/{supplier}/communication/contacts', [SupplierContactPersonController::class, 'index'])->name('communication.contacts.index')->whereNumber('supplier');
        Route::post('/{supplier}/communication/contacts', [SupplierContactPersonController::class, 'store'])->name('communication.contacts.store')->whereNumber('supplier');
        Route::put('/{supplier}/communication/contacts/{contact}', [SupplierContactPersonController::class, 'update'])->name('communication.contacts.update')->whereNumber('supplier');
        Route::delete('/{supplier}/communication/contacts/{contact}', [SupplierContactPersonController::class, 'destroy'])->name('communication.contacts.destroy')->whereNumber('supplier');
        Route::get('/{supplier}/communication/documents', [SupplierCommunicationDocumentController::class, 'index'])->name('communication.documents.index')->whereNumber('supplier');
        Route::post('/{supplier}/communication/documents', [SupplierCommunicationDocumentController::class, 'store'])->name('communication.documents.store')->whereNumber('supplier');
        Route::delete('/{supplier}/communication/documents/{document}', [SupplierCommunicationDocumentController::class, 'destroy'])->name('communication.documents.destroy')->whereNumber('supplier');
        Route::get('/{supplier}/communication/notes', [SupplierNoteController::class, 'index'])->name('communication.notes.index')->whereNumber('supplier');
        Route::post('/{supplier}/communication/notes', [SupplierNoteController::class, 'store'])->name('communication.notes.store')->whereNumber('supplier');
        Route::get('/{supplier}/communication/logs', [SupplierCommunicationLogController::class, 'index'])->name('communication.logs.index')->whereNumber('supplier');
        Route::post('/{supplier}/communication/logs', [SupplierCommunicationLogController::class, 'store'])->name('communication.logs.store')->whereNumber('supplier');
        Route::get('/{supplier}/communication/emails', [SupplierEmailHistoryController::class, 'index'])->name('communication.emails.index')->whereNumber('supplier');
        Route::get('/{supplier}/communication/audit', [SupplierCommunicationAuditController::class, 'index'])->name('communication.audit.index')->whereNumber('supplier');

        Route::get('/issue-payment-details', [SupplierIssuePaymentDetailsController::class, 'index'])->name('issue_payment_details.index');
        Route::get('/issued-payment-details', [SupplierIssuePaymentDetailsController::class, 'index'])->name('issued_payment_details.index');
        Route::get('/user-activity', [SupplierUserActivityController::class, 'index'])->name('user_activity.index');

        Route::get('/reports/index', [SupplierReportController::class, 'index'])->name('reports.index');
    });
