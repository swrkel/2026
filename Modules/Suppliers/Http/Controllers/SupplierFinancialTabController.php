<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Profile\SupplierProfileService;
use Modules\Suppliers\Utils\SupplierProfileAccessUtil;

class SupplierFinancialTabController extends Controller
{
    public function index(Supplier $supplier, SupplierProfileService $profileService)
    {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        return view('suppliers::suppliers.profile.tabs.financial_information', [
            'supplier' => $supplier,
            'financial' => $profileService->getFinancial($supplier),
            'standaloneTab' => true,
        ]);
    }
}
