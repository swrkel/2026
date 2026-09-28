<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Http\Requests\AdjustmentDecisionRequest;
use Modules\PetroPDNew\Http\Requests\AdjustmentStoreRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\Settlement\PdnewAdjustmentService;

class SettlementAdjustmentController extends PdnewController
{
    public function store(AdjustmentStoreRequest $request, int $settlement, PdnewAdjustmentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('adjustments', $this->context->businessId());

        try {
            $service->request($this->settlement($settlement), $request->validated(), $this->context->userId());
            return back()->with('success', 'Amount adjustment request submitted.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function approve(AdjustmentDecisionRequest $request, int $adjustment, PdnewAdjustmentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('adjustments', $this->context->businessId());

        try {
            $service->approve(
                $this->adjustment($adjustment),
                $this->context->userId(),
                $request->filled('approved_amount') ? (float) $request->approved_amount : null,
                $request->validated('note')
            );
            return back()->with('success', 'Amount adjustment approved.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function reject(AdjustmentDecisionRequest $request, int $adjustment, PdnewAdjustmentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('adjustments', $this->context->businessId());

        try {
            $service->reject(
                $this->adjustment($adjustment),
                $this->context->userId(),
                (string) ($request->validated('note') ?: 'Rejected')
            );
            return back()->with('success', 'Amount adjustment rejected.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
