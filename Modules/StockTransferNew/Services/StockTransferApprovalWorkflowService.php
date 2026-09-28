<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\StockTransfer;
use Modules\StockTransferNew\Entities\StockTransferApprovalMatrix;
use Modules\StockTransferNew\Entities\StockTransferApprovalStep;
use Modules\StockTransferNew\Utilities\StockTransferTenant;

class StockTransferApprovalWorkflowService
{
    public function buildApprovalSteps(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            StockTransferApprovalStep::where('transfer_id', $transfer->id)->delete();
            $matrix = $this->findMatrix($transfer);
            if (!$matrix) {
                $transfer->forceFill(['status' => 'approved', 'approved_at' => now(), 'approved_by' => Auth::id(), 'current_approval_step' => 0])->save();
                return;
            }
            foreach ($matrix->steps as $step) {
                StockTransferApprovalStep::create([
                    'transfer_id' => $transfer->id,
                    'matrix_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'role_name' => $step->role_name,
                    'approver_user_id' => $step->approver_user_id,
                    'status' => 'pending',
                ]);
            }
            $transfer->forceFill([
                'status' => 'pending_approval',
                'approval_mode' => $matrix->approval_mode,
                'approval_matrix_id' => $matrix->id,
                'current_approval_step' => 1,
            ])->save();
        });
    }

    public function approveStep(StockTransfer $transfer, ?string $remarks = null): void
    {
        DB::transaction(function () use ($transfer, $remarks) {
            $step = $this->nextPendingStep($transfer);
            if (!$step) { return; }
            $step->forceFill(['status' => 'approved', 'remarks' => $remarks, 'acted_at' => now(), 'acted_by' => Auth::id()])->save();
            $next = $this->nextPendingStep($transfer->fresh());
            if ($next) {
                $transfer->forceFill(['current_approval_step' => $next->step_order])->save();
                return;
            }
            $transfer->forceFill(['status' => 'approved', 'approved_at' => now(), 'approved_by' => Auth::id(), 'current_approval_step' => 0])->save();
        });
    }

    public function reject(StockTransfer $transfer, ?string $remarks = null): void
    {
        DB::transaction(function () use ($transfer, $remarks) {
            $step = $this->nextPendingStep($transfer);
            if ($step) {
                $step->forceFill(['status' => 'rejected', 'remarks' => $remarks, 'acted_at' => now(), 'acted_by' => Auth::id()])->save();
            }
            $transfer->forceFill(['status' => 'rejected', 'rejected_at' => now(), 'rejected_by' => Auth::id()])->save();
        });
    }

    public function returnForCorrection(StockTransfer $transfer, ?string $remarks = null): void
    {
        DB::transaction(function () use ($transfer, $remarks) {
            $step = $this->nextPendingStep($transfer);
            if ($step) {
                $step->forceFill(['status' => 'returned', 'remarks' => $remarks, 'acted_at' => now(), 'acted_by' => Auth::id()])->save();
            }
            $transfer->forceFill(['status' => 'returned_for_correction', 'returned_at' => now(), 'returned_by' => Auth::id()])->save();
        });
    }

    protected function nextPendingStep(StockTransfer $transfer): ?StockTransferApprovalStep
    {
        return StockTransferApprovalStep::where('transfer_id', $transfer->id)->where('status', 'pending')->orderBy('step_order')->first();
    }

    protected function findMatrix(StockTransfer $transfer): ?StockTransferApprovalMatrix
    {
        return StockTransferApprovalMatrix::with('steps')
            ->where('business_id', StockTransferTenant::businessId())
            ->where('is_active', true)
            ->where(function ($q) use ($transfer) { $q->whereNull('from_location_id')->orWhere('from_location_id', $transfer->from_location_id); })
            ->where(function ($q) use ($transfer) { $q->whereNull('to_location_id')->orWhere('to_location_id', $transfer->to_location_id); })
            ->where(function ($q) use ($transfer) { $q->whereNull('from_store_id')->orWhere('from_store_id', $transfer->from_store_id); })
            ->where(function ($q) use ($transfer) { $q->whereNull('to_store_id')->orWhere('to_store_id', $transfer->to_store_id); })
            ->orderByDesc('min_amount')
            ->first();
    }
}
