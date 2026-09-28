<?php
namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QrLookupController extends Controller
{
    public function form()
    {
        return view('stocktransfernew::warehouse.qr_lookup');
    }

    public function lookup(Request $request)
    {
        $request->validate(['qr_token' => 'required|string|max:100']);
        $businessId = (int)$request->session()->get('user.business_id');
        $transfer = DB::table('stock_transfer_new_transfers')
            ->where('business_id', $businessId)
            ->where('qr_token', $request->qr_token)
            ->first();
        return view('stocktransfernew::warehouse.qr_lookup', compact('transfer'));
    }
}
