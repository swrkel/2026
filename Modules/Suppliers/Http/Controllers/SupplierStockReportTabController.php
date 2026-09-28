<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Entities\SupplierPurchaseLine;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Utils\SupplierContextUtil;
use Yajra\DataTables\Facades\DataTables;

class SupplierStockReportTabController extends Controller
{
    public function index(Request $request, $supplierId = null)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $supplier = $supplierId
            ? Supplier::query()
                ->select(['id', 'name', 'contact_id', 'supplier_business_name', 'business_id', 'type'])
                ->where('business_id', $businessId)
                ->whereIn('type', ['supplier', 'both'])
                ->findOrFail((int) $supplierId)
            : null;

        return view('suppliers::stock_report.index', [
            'supplier' => $supplier,
            'stockDataUrl' => $supplier
                ? route('suppliers.stock_report.data', ['supplier' => $supplier->id])
                : null,
        ]);
    }

    public function data(Request $request, $supplierId)
    {
        abort_unless(SupplierContextUtil::can('supplier.view'), 403, 'Unauthorized action.');

        $businessId = SupplierContextUtil::businessId();
        $supplierId = (int) $supplierId;

        Supplier::query()
            ->where('business_id', $businessId)
            ->whereIn('type', ['supplier', 'both'])
            ->findOrFail($supplierId);

        $query = SupplierPurchaseLine::query()
            ->join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->join('products as p', 'purchase_lines.product_id', '=', 'p.id')
            ->where('t.business_id', $businessId)
            ->where('t.contact_id', $supplierId)
            ->whereNull('t.deleted_at')
            ->select([
                'purchase_lines.id',
                'p.name as product',
                'purchase_lines.quantity',
                'purchase_lines.purchase_price_inc_tax',
                't.transaction_date',
                't.ref_no',
                't.invoice_no',
            ]);

        return DataTables::of($query)
            ->editColumn('transaction_date', static function ($row): string {
                return $row->transaction_date
                    ? date('Y-m-d H:i', strtotime((string) $row->transaction_date))
                    : '-';
            })
            ->addColumn('reference_no', static function ($row): string {
                return (string) ($row->invoice_no ?: $row->ref_no ?: '-');
            })
            ->filterColumn('product', static function ($query, $keyword): void {
                $query->where('p.name', 'like', $keyword . '%');
            })
            ->filterColumn('reference_no', static function ($query, $keyword): void {
                $query->where(function ($referenceQuery) use ($keyword): void {
                    $referenceQuery->where('t.invoice_no', 'like', $keyword . '%')
                        ->orWhere('t.ref_no', 'like', $keyword . '%');
                });
            })
            ->orderColumn('product', 'p.name $1')
            ->orderColumn('transaction_date', 't.transaction_date $1')
            ->orderColumn('reference_no', 'COALESCE(t.invoice_no, t.ref_no) $1')
            ->make(true);
    }
}
