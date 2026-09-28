<?php

namespace Modules\MyHealthMembers\Http\Controllers\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMedicine;
use Modules\MyHealthMembers\Entities\MyHealthPharmacyStock;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;
use Modules\MyHealthMembers\Services\MyHealthStockService;

class MyHealthStockController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $ledger = MyHealthPharmacyStock::with(['medicine', 'batch'])->latest('transaction_date')->latest('id')->paginate(30);

        return view('myhealthmembers::pharmacy.stock.index', compact('ledger'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_pharmacy_stock'), 403);

        $medicines = MyHealthMedicine::where('is_active', true)->orderBy('medicine_name')->get();

        return view('myhealthmembers::pharmacy.stock.create', compact('medicines'));
    }

    public function store(Request $request, MyHealthStockService $stockService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_pharmacy_stock'), 403);

        $data = $request->validate([
            'medicine_id' => ['required', 'integer'],
            'batch_no' => ['required', 'string', 'max:255'],
            'expiry_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'transaction_date' => ['nullable', 'date'],
            'transaction_type' => ['nullable', 'string', 'max:100'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $stockService->addStock($data);

        return redirect()->route('myhealth.pharmacy.stock.index')->with('status', __('myhealthmembers::lang.stock_saved'));
    }
}
