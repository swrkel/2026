<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Http\Requests\ReconciliationResolveRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\Settlement\PdnewReconciliationService;

class ReconciliationController extends PdnewController
{
    public function evaluate(int $settlement, PdnewReconciliationService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('reconciliation', $this->context->businessId());

        try {
            $service->evaluate($this->settlement($settlement));
            return back()->with('success', 'Settlement reconciliation recalculated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function resolve(ReconciliationResolveRequest $request, int $issue, PdnewReconciliationService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('reconciliation', $this->context->businessId());

        try {
            $service->resolve($this->issue($issue), $this->context->userId(), (string) $request->validated('note'));
            return back()->with('success', 'Reconciliation issue marked as resolved.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
