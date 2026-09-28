<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Services\SupplierQueryService;

class SupplierDashboardController extends Controller
{
    public function __construct(private SupplierQueryService $supplierQueryService)
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $totalSuppliers = $this->supplierQueryService->baseQuery()->count();
        return view('suppliers::dashboard.index', compact('totalSuppliers'));
    }
}
