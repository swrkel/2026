<?php

namespace Modules\MyHealthMembers\Http\Controllers\Pharmacy;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthDispense;
use Modules\MyHealthMembers\Entities\MyHealthDispenseItem;
use Modules\MyHealthMembers\Entities\MyHealthMedicine;
use Modules\MyHealthMembers\Entities\MyHealthMedicineBatch;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthDispenseNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;
use Modules\MyHealthMembers\Services\MyHealthStockService;

class MyHealthDispenseController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $query = MyHealthDispense::with(['member', 'items'])->latest('id');

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('dispense_no', 'like', "%{$search}%")
                    ->orWhereHas('member', function ($memberQuery) use ($search) {
                        $memberQuery->where('myhealth_code', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%")
                            ->orWhere('nic_no', 'like', "%{$search}%");
                    });
            });
        }

        $dispenses = $query->paginate(25);

        return view('myhealthmembers::pharmacy.dispensing.index', compact('dispenses'));
    }

    public function create(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_dispense_medicine'), 403);

        $members = MyHealthMember::orderByDesc('id')->limit(100)->get();
        $medicines = MyHealthMedicine::where('is_active', true)->with('batches')->orderBy('medicine_name')->get();
        $prescriptions = collect();

        if ($request->filled('member_id')) {
            $prescriptions = MyHealthPrescription::where('member_id', $request->integer('member_id'))->latest()->get();
        }

        return view('myhealthmembers::pharmacy.dispensing.create', compact('members', 'medicines', 'prescriptions'));
    }

    public function store(Request $request, MyHealthDispenseNumberService $numberService, MyHealthStockService $stockService, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_dispense_medicine'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'prescription_id' => ['nullable', 'integer'],
            'dispense_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['nullable', 'integer'],
            'items.*.batch_id' => ['nullable', 'integer'],
            'items.*.dosage' => ['nullable', 'string', 'max:255'],
            'items.*.frequency' => ['nullable', 'string', 'max:255'],
            'items.*.duration' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.instructions' => ['nullable', 'string'],
        ]);

        $data['items'] = collect($data['items'])
            ->filter(fn ($item) => !empty($item['medicine_id']) && !empty($item['quantity']))
            ->values()
            ->all();

        if (empty($data['items'])) {
            return back()->withErrors(['items' => 'Please enter at least one medicine item.'])->withInput();
        }

        $dispense = DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($data, $numberService, $stockService, $permissionService) {
            $dispense = MyHealthDispense::create([
                'dispense_no' => $numberService->nextNumber(),
                'member_id' => $data['member_id'],
                'business_id' => $permissionService->businessId(),
                'prescription_id' => $data['prescription_id'] ?? null,
                'pharmacist_user_id' => auth()->id(),
                'dispense_date' => $data['dispense_date'] ?? date('Y-m-d'),
                'status' => 'dispensed',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $medicine = MyHealthMedicine::findOrFail($item['medicine_id']);
                $quantity = (float) $item['quantity'];
                $batch = !empty($item['batch_id'])
                    ? MyHealthMedicineBatch::findOrFail($item['batch_id'])
                    : MyHealthMedicineBatch::where('medicine_id', $medicine->id)
                        ->where('available_qty', '>', 0)
                        ->where(function ($q) { $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', date('Y-m-d')); })
                        ->orderByRaw('expiry_date is null')
                        ->orderBy('expiry_date')
                        ->orderBy('id')
                        ->firstOrFail();

                $unitPrice = (float) ($item['unit_price'] ?? $batch->selling_price ?? 0);
                $lineTotal = $quantity * $unitPrice;

                MyHealthDispenseItem::create([
                    'dispense_id' => $dispense->id,
                    'medicine_id' => $medicine->id,
                    'batch_id' => $batch->id,
                    'medicine_name' => $medicine->medicine_name,
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'instructions' => $item['instructions'] ?? null,
                ]);

                $stockService->dispenseFromBatch($batch, $quantity, $dispense->dispense_no, 'Medicine dispensed to member.');
                $total += $lineTotal;
            }

            $dispense->total_amount = $total;
            $dispense->save();

            return $dispense;
        });

        $auditService->log($dispense->member_id, 'pharmacy', 'dispense', 'Medicine dispensed: ' . $dispense->dispense_no);

        return redirect()->route('myhealth.pharmacy.dispensing.show', $dispense->id)->with('status', __('myhealthmembers::lang.dispense_saved'));
    }

    public function show(MyHealthDispense $dispense, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_pharmacy'), 403);

        $dispense->load(['member', 'prescription', 'items.medicine', 'items.batch']);

        return view('myhealthmembers::pharmacy.dispensing.show', compact('dispense'));
    }
}
