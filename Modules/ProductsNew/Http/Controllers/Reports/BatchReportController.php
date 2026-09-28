<?php

namespace Modules\ProductsNew\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class BatchReportController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('products_new_batches')) {
            $rows = DB::table('products')->whereRaw('1 = 0')->paginate(100);
            return view('productsnew::reports.batch', compact('rows'));
        }

        $columns = Schema::getColumnListing('products_new_batches');
        $query = DB::table('products_new_batches as b');
        $select = [
            $this->columnOrAlias($columns, 'batch_no', "''", 'batch_no'),
            $this->columnOrAlias($columns, 'lot_no', 'NULL', 'lot_no'),
            $this->columnOrAlias($columns, 'product_id', 'NULL', 'product_id'),
            $this->columnOrAlias($columns, 'location_id', 'NULL', 'location_id'),
            $this->columnOrAlias($columns, 'current_qty', '0', 'current_qty'),
            $this->columnOrAlias($columns, 'available_qty', '0', 'available_qty'),
            $this->columnOrAlias($columns, 'cost_price', '0', 'cost_price'),
            $this->columnOrAlias($columns, 'selling_price', '0', 'selling_price'),
        ];

        if (in_array('expiry_at', $columns, true)) {
            $select[] = 'b.expiry_at';
        } elseif (in_array('expiry_date', $columns, true)) {
            $select[] = DB::raw('b.expiry_date as expiry_at');
        } else {
            $select[] = DB::raw('NULL as expiry_at');
        }

        $query->select($select);

        if (in_array('business_id', $columns, true)) {
            $query->where('b.business_id', ProductsNewTenantGuard::businessId());
        }
        if ($request->filled('product_id') && in_array('product_id', $columns, true)) {
            $query->where('b.product_id', (int) $request->input('product_id'));
        }
        if ($request->filled('location_id') && in_array('location_id', $columns, true)) {
            $query->where('b.location_id', (int) $request->input('location_id'));
        }
        if ($request->filled('batch_no') && in_array('batch_no', $columns, true)) {
            $query->where('b.batch_no', 'like', '%' . trim((string) $request->input('batch_no')) . '%');
        }

        $expiryColumn = in_array('expiry_at', $columns, true)
            ? 'b.expiry_at'
            : (in_array('expiry_date', $columns, true) ? 'b.expiry_date' : null);

        if ($expiryColumn !== null && $request->filled('expiry_status')) {
            $today = now()->toDateString();
            $status = (string) $request->input('expiry_status');
            if ($status === 'expired') {
                $query->whereDate($expiryColumn, '<', $today);
            } elseif ($status === 'expiring_soon') {
                $query->whereBetween($expiryColumn, [$today, now()->addDays(30)->toDateString()]);
            } elseif ($status === 'valid') {
                $query->whereDate($expiryColumn, '>=', $today);
            }
        }

        if ($expiryColumn !== null) {
            $query->orderByRaw('CASE WHEN ' . $expiryColumn . ' IS NULL THEN 1 ELSE 0 END ASC')
                ->orderBy($expiryColumn);
        }

        $query->orderByDesc(in_array('id', $columns, true) ? 'b.id' : 'b.batch_no');
        $rows = $query->paginate(100)->appends($request->all());

        return view('productsnew::reports.batch', compact('rows'));
    }

    protected function columnOrAlias(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? 'b.' . $column
            : DB::raw($fallback . ' as ' . $alias);
    }
}
