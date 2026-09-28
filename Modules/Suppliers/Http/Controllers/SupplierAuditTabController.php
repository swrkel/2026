<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Audit\SupplierAuditService;
use Modules\Suppliers\Utils\SupplierProfileAccessUtil;

class SupplierAuditTabController extends Controller
{
    public function index(Supplier $supplier, SupplierAuditService $auditService)
    {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        return view('suppliers::suppliers.profile.tabs.audit_trail', [
            'supplier' => $supplier,
            'auditTrail' => $auditService->timeline($supplier),
            'standaloneTab' => true,
        ]);
    }
}
