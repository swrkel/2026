<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class BalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = DB::table('stnew_stock_balances')->where('business_id', StockTransferTenant::businessId());
        if ($request->filled('location_id')) {
            $query->where('business_location_id', $request->location_id);
        }
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        $balances = $query->orderByDesc('updated_at')->paginate(30);
        return view('stocktransfernew::balances.index', compact('balances'));
    }
}
