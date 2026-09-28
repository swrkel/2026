<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Http\Requests\ReasonRequest;
use Modules\PetroPDNew\Http\Requests\WorkflowRequest;
use Modules\PetroPDNew\Services\Settlement\PdnewWorkflowService;

class SettlementWorkflowController extends PdnewController
{
    public function submit(WorkflowRequest $request, int $settlement, PdnewWorkflowService $service)
    {
        return $this->perform(fn () => $service->submitForReview(
            $this->settlement($settlement), $this->context->userId(), $request->validated('note')
        ), 'Settlement submitted for review.');
    }

    public function returnToDraft(ReasonRequest $request, int $settlement, PdnewWorkflowService $service)
    {
        return $this->perform(fn () => $service->returnToDraft(
            $this->settlement($settlement), $this->context->userId(), (string) $request->validated('reason')
        ), 'Settlement returned to draft.');
    }

    public function approve(WorkflowRequest $request, int $settlement, PdnewWorkflowService $service)
    {
        return $this->perform(fn () => $service->approve(
            $this->settlement($settlement), $this->context->userId(), $request->validated('note')
        ), 'Settlement approved.');
    }

    public function finalize(WorkflowRequest $request, int $settlement, PdnewWorkflowService $service)
    {
        return $this->perform(fn () => $service->finalize(
            $this->settlement($settlement), $this->context->userId(), $request->validated('note')
        ), 'Settlement finalized and linked back to Pumper Dashboard-New.');
    }

    public function reopen(ReasonRequest $request, int $settlement, PdnewWorkflowService $service)
    {
        return $this->perform(fn () => $service->reopen(
            $this->settlement($settlement), $this->context->userId(), (string) $request->validated('reason')
        ), 'Settlement reopened.');
    }

    private function perform(callable $callback, string $message)
    {
        try {
            $settlement = $callback();
            return redirect()->route('petro-pd-new.settlements.show', $settlement->id)->with('success', $message);
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
