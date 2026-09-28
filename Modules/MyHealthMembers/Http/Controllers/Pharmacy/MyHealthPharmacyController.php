<?php

namespace Modules\MyHealthMembers\Http\Controllers\Pharmacy;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDispense;
use Modules\MyHealthMembers\Entities\MyHealthMedicine;
use Modules\MyHealthMembers\Entities\MyHealthMedicineBatch;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthPharmacyController extends Controller
{
    public function dashboard(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $totalMedicines = MyHealthMedicine::count();
        $lowStockMedicines = MyHealthMedicine::whereRaw('(select coalesce(sum(available_qty),0) from myhealth_medicine_batches where myhealth_medicine_batches.medicine_id = myhealth_medicines.id) <= reorder_level')->count();
        $expiringBatches = MyHealthMedicineBatch::whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays(60)->toDateString())
            ->where('available_qty', '>', 0)
            ->count();
        $pendingDispenses = MyHealthDispense::whereIn('status', ['pending', 'partially_dispensed'])->count();

        return view('myhealthmembers::pharmacy.dashboard', compact('totalMedicines', 'lowStockMedicines', 'expiringBatches', 'pendingDispenses'));
    }
}
