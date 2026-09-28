<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeaseAssetType;

class LeaseAssetTypeController extends Controller
{
    public function index()
    {
        $types = LeaseAssetType::orderBy('name')->paginate(20);
        return view('leasing::lease_asset_types.index', compact('types'));
    }

    public function create()
    {
        $type = new LeaseAssetType();
        return view('leasing::lease_asset_types.form', ['type' => $type, 'action' => route('leasing.collateral-types.store')]);
    }

    public function store(Request $request)
    {
        LeaseAssetType::create($request->only(['business_id','name','code','description','requires_weight','requires_purity','status']));
        return redirect()->route('leasing.collateral-types.index')->with('status', ['success' => 1, 'msg' => 'Collateral type saved successfully']);
    }

    public function edit($id)
    {
        $type = LeaseAssetType::findOrFail($id);
        return view('leasing::lease_asset_types.form', ['type' => $type, 'action' => route('leasing.collateral-types.update', $id)]);
    }

    public function update(Request $request, $id)
    {
        LeaseAssetType::findOrFail($id)->update($request->only(['business_id','name','code','description','requires_weight','requires_purity','status']));
        return redirect()->route('leasing.collateral-types.index')->with('status', ['success' => 1, 'msg' => 'Collateral type updated successfully']);
    }
}
