<?php
namespace Modules\Suppliers\Http\Controllers;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller; use Illuminate\Http\Request; use Modules\Suppliers\Entities\Supplier;
class SupplierAccountTabController extends Controller { public function index(Supplier $supplier){ $this->check($supplier); return view('suppliers::accounts.index', compact('supplier')); } public function update(Request $request, Supplier $supplier){ $this->check($supplier); return back()->with('status', __('suppliers::lang.account_settings_updated_successfully')); } private function check(Supplier $supplier): void { abort_unless((int)$supplier->business_id === (int)\Modules\Suppliers\Utils\SupplierContextUtil::businessId() && $supplier->type === 'supplier', 404); } }
