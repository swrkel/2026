<?php

namespace Modules\StockAdjustmentNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentReason;
use Modules\StockAdjustmentNew\Services\TenantScopeService;

class ReasonController extends Controller
{
    public function index(Request $request, TenantScopeService $scope)
    {
        $businessId = $scope->businessId($request);
        $reasons = StockAdjustmentReason::query()
            ->where(function ($query) use ($businessId): void {
                $query->whereNull('business_id');
                if ($businessId) {
                    $query->orWhere('business_id', $businessId);
                }
            })
            ->orderByRaw('business_id IS NULL DESC')
            ->orderBy('name')
            ->paginate(25);

        return view('stockadjustmentnew::reasons.index', compact('reasons'));
    }

    public function create()
    {
        return view('stockadjustmentnew::reasons.create');
    }

    public function store(Request $request, TenantScopeService $scope)
    {
        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:50',
            'effect' => 'required|string|in:increase,decrease,both',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['business_id'] = $scope->businessId($request);
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active'] = $request->boolean('is_active', true);

        StockAdjustmentReason::create($data);

        return redirect()->route('stock-adjustment-new.reasons.index')->with('status', 'Reason saved.');
    }

    public function edit(Request $request, StockAdjustmentReason $reason, TenantScopeService $scope)
    {
        $this->assertEditable($reason, $scope->businessId($request));

        return view('stockadjustmentnew::reasons.edit', compact('reason'));
    }

    public function update(Request $request, StockAdjustmentReason $reason, TenantScopeService $scope)
    {
        $this->assertEditable($reason, $scope->businessId($request));

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:50',
            'effect' => 'required|string|in:increase,decrease,both',
            'requires_approval' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active'] = $request->boolean('is_active', true);
        $reason->update($data);

        return redirect()->route('stock-adjustment-new.reasons.index')->with('status', 'Reason updated.');
    }

    public function destroy(Request $request, StockAdjustmentReason $reason, TenantScopeService $scope)
    {
        $this->assertEditable($reason, $scope->businessId($request));
        $reason->delete();

        return back()->with('status', 'Reason deleted.');
    }

    private function assertEditable(StockAdjustmentReason $reason, ?int $businessId): void
    {
        abort_if($reason->business_id === null, 403, 'System adjustment reasons cannot be changed.');
        abort_unless((int) $reason->business_id === (int) $businessId, 404);
    }
}
