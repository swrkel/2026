<?php

namespace Modules\PriceChangeNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PriceChangeNew\Entities\PriceChange;
use Modules\PriceChangeNew\Services\PriceApplicationService;
use Modules\PriceChangeNew\Services\PriceChangeContext;
use Modules\PriceChangeNew\Services\PriceChangeSettingsService;
use Modules\PriceChangeNew\Services\PriceChangeWorkflowService;

class WorkflowController extends Controller
{
    public function __construct(
        private PriceChangeContext $context,
        private PriceChangeWorkflowService $workflow,
        private PriceApplicationService $applications,
        private PriceChangeSettingsService $settings
    ) {
    }

    public function submit(Request $request, int $id)
    {
        $change = $this->workflow->submit($this->find($id), (int) auth()->id());
        $message = $change->status === 'submitted'
            ? $change->reference_no . ' was submitted for approval.'
            : ($change->status === 'scheduled'
                ? $change->reference_no . ' was submitted and scheduled because approval is disabled.'
                : $change->reference_no . ' was submitted and approved because approval is disabled.');

        $success = true;
        if ($change->status === 'approved'
            && (bool) $this->settings->get($change->business_id, 'auto_apply_on_approval', false)) {
            $application = $this->applications->apply($change, (int) auth()->id(), 'submission');
            $success = in_array($application->status, ['success', 'partial'], true);
            $message .= ' Application status: ' . ucfirst($application->status) . '.';
            if (! $success && $application->message) {
                $message .= ' ' . $application->message;
            }
        }

        return $this->workflowResponse($request, $success, $message, $change->fresh());
    }

    public function approve(Request $request, int $id)
    {
        $request->validate(['notes' => ['nullable', 'string', 'max:5000']]);
        $change = $this->workflow->approve($this->find($id), (int) auth()->id(), $request->input('notes'));
        $message = $change->status === 'scheduled'
            ? $change->reference_no . ' was approved and scheduled.'
            : $change->reference_no . ' was approved.';

        $success = true;
        if ($change->status === 'approved'
            && (bool) $this->settings->get($change->business_id, 'auto_apply_on_approval', false)) {
            $application = $this->applications->apply($change, (int) auth()->id(), 'approval');
            $success = in_array($application->status, ['success', 'partial'], true);
            $message .= ' Application status: ' . ucfirst($application->status) . '.';
            if (! $success && $application->message) {
                $message .= ' ' . $application->message;
            }
        }

        return $this->workflowResponse($request, $success, $message, $change->fresh());
    }

    public function reject(Request $request, int $id)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $change = $this->workflow->reject($this->find($id), (int) auth()->id(), $validated['reason']);

        return $this->workflowResponse($request, true, $change->reference_no . ' was rejected.', $change);
    }

    public function cancel(Request $request, int $id)
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:5000']]);
        $change = $this->workflow->cancel($this->find($id), (int) auth()->id(), $request->input('reason'));

        return $this->workflowResponse($request, true, $change->reference_no . ' was cancelled.', $change);
    }

    public function apply(Request $request, int $id)
    {
        $change = $this->find($id);
        $application = $this->applications->apply($change, (int) auth()->id(), 'manual');
        $success = in_array($application->status, ['success', 'partial'], true);
        $message = $application->message ?: ($application->status === 'success'
            ? $change->reference_no . ' was applied successfully.'
            : 'Price application failed.');

        return $this->workflowResponse($request, $success, $message, $change->fresh());
    }

    public function applyDue(Request $request)
    {
        $summary = $this->applications->applyDue($this->context->businessId(), 100, false);
        $message = sprintf(
            'Due processing completed. Processed: %d, Applied: %d, Partial: %d, Failed: %d.',
            $summary['processed'], $summary['applied'], $summary['partial'], $summary['failed']
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(array_merge(['success' => true, 'message' => $message], $summary));
        }

        return redirect()->route('pricechangenew.dashboard')->with('status', $message);
    }

    private function find(int $id): PriceChange
    {
        return PriceChange::query()
            ->forBusiness($this->context->businessId())
            ->whereKey($id)
            ->firstOrFail();
    }

    /*
     * MA-008: renamed from respond() to workflowResponse().
     *
     * App\Http\Controllers\Controller - which this class extends - already
     * declares a PUBLIC respond($data). Redeclaring it private here reduced the
     * visibility of an inherited method, which PHP forbids outright:
     *
     *     Access level to ...WorkflowController::respond() must be public
     *     (as in class App\Http\Controllers\Controller)
     *
     * That is a compile-time error, so the class could not load at all and
     * every workflow action - Submit for Approval, Approve, Reject, Cancel -
     * failed the moment it was clicked.
     *
     * Making it public would not have been enough: the signatures also differ
     * (four arguments here against the parent's one), so PHP would then have
     * rejected it as an incompatible declaration. Renaming avoids both, and the
     * method is private to this controller - all five call sites are in this
     * file - so nothing outside it is affected.
     */
    private function workflowResponse(Request $request, bool $success, string $message, PriceChange $change)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => $success,
                'message' => $message,
                'status' => $change->status,
                'redirect' => route('pricechangenew.changes.show', $change->id),
            ], $success ? 200 : 422);
        }

        return redirect()->route('pricechangenew.changes.show', $change->id)
            ->with($success ? 'status' : 'error', $message);
    }
}
