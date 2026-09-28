<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Audit\SupplierAuditService;
use Modules\Suppliers\Services\Documents\SupplierDocumentService;
use Modules\Suppliers\Services\Ledger\SupplierLedgerSummaryService;
use Modules\Suppliers\Services\Notes\SupplierNoteService;
use Modules\Suppliers\Services\Profile\SupplierProfileService;
use Modules\Suppliers\Services\Profile\SupplierPurchaseSummaryService;
use Modules\Suppliers\Utils\SupplierProfileAccessUtil;

class SupplierProfileTabController extends Controller
{
    public function index(
        Supplier $supplier,
        SupplierProfileService $profileService,
        SupplierLedgerSummaryService $ledgerSummaryService,
        SupplierPurchaseSummaryService $purchaseSummaryService,
        SupplierDocumentService $documentService,
        SupplierNoteService $noteService,
        SupplierAuditService $auditService
    ) {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        return view('suppliers::profile.index', [
            'supplier' => $supplier,
            'summary' => $profileService->getSummary($supplier),
            'address' => $profileService->getAddress($supplier),
            'financial' => $profileService->getFinancial($supplier),
            'ledgerSummary' => $ledgerSummaryService->summary($supplier),
            'recentPurchases' => $purchaseSummaryService->recentPurchases($supplier),
            'documents' => $documentService->list($supplier),
            'notes' => $noteService->latest($supplier),
            'auditTrail' => $auditService->timeline($supplier),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        $supplier->update($request->only([
            'name', 'supplier_business_name', 'mobile', 'alternate_number', 'landline', 'email',
            'tax_number', 'address_line_1', 'address_line_2', 'city', 'state', 'country',
            'zip_code', 'landmark', 'pay_term_number', 'pay_term_type', 'credit_limit',
        ]));

        return back()->with('status', __('suppliers::lang.profile_updated_successfully'));
    }
}
