<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;

class SupplierImportTabController extends Controller
{
    public function index()
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.create'), 403, 'Unauthorized action.');
        return view('suppliers::imports.index');
    }

    public function openingBalance()
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.create'), 403, 'Unauthorized action.');
        return view('suppliers::imports.opening_balance');
    }

    public function postImport(Request $request)
    {
        abort_unless(\Modules\Suppliers\Utils\SupplierContextUtil::can('supplier.create'), 403, 'Unauthorized action.');
        return back()->with('status', [
            'success' => 0,
            'msg' => 'Import posting is intentionally kept for SUP-003 after copying the current production import validation exactly.',
        ]);
    }
}
