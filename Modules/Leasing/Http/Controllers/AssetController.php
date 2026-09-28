<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Leasing\Models\AssetLocation;

class AssetController extends Controller
{
    public function index()
    {
        $assets = AssetLocation::orderBy('asset_name')->paginate(20);
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        return view('leasing::asset.index', compact('assets', 'locations'));
    }

    public function store(Request $request)
    {
        AssetLocation::create($request->all());
        return redirect()->route('leasing.asset.index')->with('status', ['success' => 1, 'msg' => 'Asset location saved successfully']);
    }
}
