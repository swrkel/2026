<?php

namespace Modules\PetroDirect\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class DailyCardController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');
            $query = DB::table('petro_daily_cards')->where('business_id', $business_id);

            if ($request->filled('daily_collection_id')) {
                $query->where('daily_collection_id', $request->daily_collection_id);
            }

            return DataTables::of($query)->make(true);
        }

        return response()->json(['success' => true, 'data' => []]);
    }

    public function create(Request $request)
    {
        return response('<div class="modal-body"><p>Daily card entry form is not available in this PetroDirect package.</p></div>');
    }

    public function store(Request $request)
    {
        return response()->json(['success' => false, 'msg' => 'Daily card save is not available in this PetroDirect package.']);
    }

    public function update(Request $request, $id)
    {
        return response()->json(['success' => false, 'msg' => 'Daily card update is not available in this PetroDirect package.']);
    }
}
