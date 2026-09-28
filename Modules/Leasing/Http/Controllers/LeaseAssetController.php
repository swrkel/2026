<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Leasing\Models\LeaseAsset;
use Modules\Leasing\Models\LeaseAssetType;
use Modules\Leasing\Models\AssetLocation;
use Modules\Leasing\Services\LeasingNumberService;

class LeaseAssetController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaseAsset::with(['collateralType','assetLocation'])->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('lease_asset_no', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }
        $lease_assets = $query->paginate(20);
        return view('leasing::lease_assets.index', compact('lease_assets'));
    }

    public function create(LeasingNumberService $numberService)
    {
        $lease_asset = new LeaseAsset(['lease_asset_no' => $numberService->nextLeaseAssetNo()]);
        return $this->form($lease_asset, route('leasing.lease_assets.store'));
    }

    public function store(Request $request)
    {
        LeaseAsset::create($request->all());
        return redirect()->route('leasing.lease_assets.index')->with('status', ['success' => 1, 'msg' => 'LeaseAsset saved successfully']);
    }

    public function show($id)
    {
        $lease_asset = LeaseAsset::with(['collateralType','assetLocation'])->findOrFail($id);
        return view('leasing::lease_assets.show', compact('lease_asset'));
    }

    public function edit($id)
    {
        $lease_asset = LeaseAsset::findOrFail($id);
        return $this->form($lease_asset, route('leasing.lease_assets.update', $id));
    }

    public function update(Request $request, $id)
    {
        LeaseAsset::findOrFail($id)->update($request->all());
        return redirect()->route('leasing.lease_assets.index')->with('status', ['success' => 1, 'msg' => 'LeaseAsset updated successfully']);
    }

    protected function form(LeaseAsset $lease_asset, $action)
    {
        $types = LeaseAssetType::orderBy('name')->pluck('name', 'id');
        $assets = AssetLocation::orderBy('asset_name')->get()->pluck('asset_label', 'id');
        if ($assets->isEmpty()) {
            $assets = AssetLocation::orderBy('asset_name')->pluck('asset_name', 'id');
        }
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        return view('leasing::lease_assets.form', compact('lease_asset', 'action', 'types', 'assets', 'locations'));
    }
}
