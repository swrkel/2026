<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Leasing\Models\LeaseAsset;
use Modules\Leasing\Models\LeasingProduct;
use Modules\Leasing\Models\LeaseContract;
use Modules\Leasing\Services\LeasingNumberService;
use Modules\Leasing\Services\LeaseContractService;

class LeaseContractController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaseContract::with('product')->orderBy('id', 'desc');
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('lease_contract_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhere('customer_mobile', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        $lease_contracts = $query->paginate(20);
        return view('leasing::lease_contracts.index', compact('lease_contracts'));
    }

    public function create(LeasingNumberService $numberService)
    {
        $lease_contract = new LeaseContract(['lease_contract_no' => $numberService->nextLeaseContractNo(), 'lease_contractd_on' => date('Y-m-d'), 'due_on' => date('Y-m-d', strtotime('+30 days'))]);
        return $this->form($lease_contract, route('leasing.lease_contracts.store'));
    }

    public function store(Request $request, LeaseContractService $service)
    {
        $data = $request->except(['lease_asset_ids']);
        $data['outstanding_amount'] = $data['outstanding_amount'] ?? ($data['advance_amount'] ?? 0);
        $service->create($data, (array) $request->get('lease_asset_ids', []));
        return redirect()->route('leasing.lease_contracts.index')->with('status', ['success' => 1, 'msg' => 'LeaseContract saved successfully']);
    }

    public function show($id)
    {
        $lease_contract = LeaseContract::with(['product','lease_assets','transactions'])->findOrFail($id);
        return view('leasing::lease_contracts.show', compact('lease_contract'));
    }

    public function edit($id)
    {
        $lease_contract = LeaseContract::with('lease_assets')->findOrFail($id);
        return $this->form($lease_contract, route('leasing.lease_contracts.update', $id));
    }

    public function update(Request $request, $id)
    {
        $lease_contract = LeaseContract::findOrFail($id);
        $data = $request->except(['lease_asset_ids']);
        $data['outstanding_amount'] = $data['outstanding_amount'] ?? ($data['advance_amount'] ?? 0);
        $lease_contract->update($data);
        $sync = [];
        foreach ((array) $request->get('lease_asset_ids', []) as $lease_assetId) {
            if ($lease_assetId) {
                $sync[$lease_assetId] = ['lease_asset_value' => $data['assessed_value'] ?? 0];
            }
        }
        $lease_contract->lease_assets()->sync($sync);
        return redirect()->route('leasing.lease_contracts.index')->with('status', ['success' => 1, 'msg' => 'LeaseContract updated successfully']);
    }

    protected function form(LeaseContract $lease_contract, $action)
    {
        $products = LeasingProduct::orderBy('name')->pluck('name', 'id');
        $lease_assets = LeaseAsset::where('status', 'available')->orWhereIn('id', $lease_contract->lease_assets->pluck('id')->toArray())->orderBy('lease_asset_no')->get();
        $locations = DB::table('business_locations')->orderBy('name')->pluck('name', 'id');
        $selectedLeaseAssets = $lease_contract->exists ? $lease_contract->lease_assets->pluck('id')->toArray() : [];
        return view('leasing::lease_contracts.form', compact('lease_contract', 'action', 'products', 'lease_assets', 'locations', 'selectedLeaseAssets'));
    }
}
