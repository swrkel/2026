<?php

namespace Modules\MyHealthMembers\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthClaimSettlement;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Services\MyHealthClaimSettlementNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthClaimSettlementController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_billing'), 403);
        return view('myhealthmembers::billing.claims.index', [
            'settlements' => MyHealthClaimSettlement::query()->with('claim.member')->latest('id')->paginate(25),
        ]);
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        return view('myhealthmembers::billing.claims.create', [
            'claims' => MyHealthInsuranceClaim::query()->with('member')->whereIn('status', ['submitted', 'approved'])->latest('id')->get(),
        ]);
    }

    public function store(Request $request, MyHealthClaimSettlementNumberService $numberService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        $data = $request->validate([
            'claim_id' => ['required', 'integer'],
            'settlement_date' => ['nullable', 'date'],
            'approved_amount' => ['required', 'numeric'],
            'settled_amount' => ['required', 'numeric'],
            'status' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::connection(config('myhealthmembers.central_connection'))->transaction(function () use ($data, $numberService) {
            $claim = MyHealthInsuranceClaim::query()->findOrFail($data['claim_id']);
            MyHealthClaimSettlement::create([
                'settlement_no' => $numberService->nextNumber(),
                'claim_id' => $claim->id,
                'settlement_date' => $data['settlement_date'] ?? date('Y-m-d'),
                'approved_amount' => (float) $data['approved_amount'],
                'settled_amount' => (float) $data['settled_amount'],
                'status' => $data['status'] ?? 'settled',
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $claim->update([
                'approved_amount' => (float) $data['approved_amount'],
                'settled_amount' => (float) $data['settled_amount'],
                'status' => $data['status'] ?? 'settled',
            ]);
        });

        return redirect()->route('myhealth.billing.claims.index')->with('status', __('myhealthmembers::lang.claim_settlement_saved'));
    }
}
