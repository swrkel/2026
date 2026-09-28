<?php

namespace Modules\ProductsNew\Http\Controllers\Reports;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class PriceChangeReportController extends Controller
{
    public function index()
    {
        if (! Schema::hasTable('products_new_price_history')) {
            $rows = DB::table('products')->whereRaw('1 = 0')->paginate(50);
            return view('productsnew::reports.price_changes', compact('rows'));
        }

        $columns = Schema::getColumnListing('products_new_price_history');
        $query = DB::table('products_new_price_history as ph');
        $hasProducts = Schema::hasTable('products')
            && Schema::hasColumn('products', 'id')
            && Schema::hasColumn('products', 'name');

        if ($hasProducts && in_array('product_id', $columns, true)) {
            $query->leftJoin('products as p', 'p.id', '=', 'ph.product_id')
                ->addSelect('p.name as product_name');
        } else {
            $query->selectRaw("'' as product_name");
        }

        $query->addSelect('ph.*');

        if (in_array('business_id', $columns, true)) {
            $query->where('ph.business_id', ProductsNewTenantGuard::businessId());
        }

        $orderColumn = in_array('effective_from', $columns, true)
            ? 'ph.effective_from'
            : (in_array('created_at', $columns, true) ? 'ph.created_at' : 'ph.id');

        $rows = $query->orderByDesc($orderColumn)->orderByDesc('ph.id')->paginate(50);

        return view('productsnew::reports.price_changes', compact('rows'));
    }
}
