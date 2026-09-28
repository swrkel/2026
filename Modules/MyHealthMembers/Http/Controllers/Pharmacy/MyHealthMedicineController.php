<?php

namespace Modules\MyHealthMembers\Http\Controllers\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMedicine;
use Modules\MyHealthMembers\Services\MyHealthMedicineCodeService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthMedicineController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $query = MyHealthMedicine::query()->with('batches');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('medicine_code', 'like', "%{$search}%")
                    ->orWhere('medicine_name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $medicines = $query->orderBy('medicine_name')->paginate(25);

        return view('myhealthmembers::pharmacy.medicines.index', compact('medicines'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        return view('myhealthmembers::pharmacy.medicines.create');
    }

    public function store(Request $request, MyHealthMedicineCodeService $codeService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $data = $request->validate([
            'medicine_name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'dosage_form' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['medicine_code'] = $codeService->nextCode();
        $data['business_id'] = $permissionService->businessId();
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);

        MyHealthMedicine::create($data);

        return redirect()->route('myhealth.pharmacy.medicines.index')->with('status', __('myhealthmembers::lang.medicine_saved'));
    }
}
