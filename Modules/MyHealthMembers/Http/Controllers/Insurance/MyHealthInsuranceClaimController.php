<?php

namespace Modules\MyHealthMembers\Http\Controllers\Insurance;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Entities\MyHealthInsurancePolicy;
use Modules\MyHealthMembers\Services\MyHealthClaimNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthInsuranceClaimController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $query = MyHealthInsuranceClaim::query()->with(['member', 'policy.company']);
        if ($search = trim((string) $request->input('search'))) {
            $query->where('claim_no', 'like', "%{$search}%")
                ->orWhereHas('member', function ($q) use ($search) {
                    $q->where('myhealth_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
        }

        $claims = $query->latest('id')->paginate(25);
        return view('myhealthmembers::insurance.claims.index', compact('claims'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);
        return view('myhealthmembers::insurance.claims.create', [
            'policies' => MyHealthInsurancePolicy::query()->with(['member', 'company'])->where('status', 'active')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request, MyHealthClaimNumberService $numberService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $data = $request->validate([
            'policy_id' => ['required', 'integer'],
            'claim_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
        ]);

        $policy = MyHealthInsurancePolicy::query()->findOrFail($data['policy_id']);
        $items = collect($request->input('items', []))->filter(fn ($item) => !empty($item['description']) && (float) ($item['amount'] ?? 0) > 0);
        $claimAmount = $items->sum(fn ($item) => (float) $item['amount']);

        DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($data, $policy, $items, $claimAmount, $numberService) {
            $claim = MyHealthInsuranceClaim::create([
                'claim_no' => $numberService->nextNumber(),
                'member_id' => $policy->member_id,
                'policy_id' => $policy->id,
                'claim_date' => $data['claim_date'] ?? date('Y-m-d'),
                'claim_amount' => $claimAmount,
                'approved_amount' => 0,
                'settled_amount' => 0,
                'status' => 'submitted',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $claim->items()->create([
                    'item_type' => $item['item_type'] ?? null,
                    'description' => $item['description'],
                    'service_date' => $item['service_date'] ?? null,
                    'amount' => (float) $item['amount'],
                    'approved_amount' => 0,
                ]);
            }
        });

        return redirect()->route('myhealth.insurance.claims.index')->with('status', __('myhealthmembers::lang.insurance_claim_saved'));
    }

    public function show(MyHealthInsuranceClaim $claim, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);
        $claim->load(['member', 'policy.company', 'items']);
        return view('myhealthmembers::insurance.claims.show', compact('claim'));
    }
}
