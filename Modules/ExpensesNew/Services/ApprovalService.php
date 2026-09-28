<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Entities\ApprovalLog;
use Modules\ExpensesNew\Entities\ApprovalWorkflow;
use Modules\ExpensesNew\Entities\Expense;

class ApprovalService
{
    public function activeWorkflowFor(Expense $expense): ?ApprovalWorkflow
    {
        return ApprovalWorkflow::where('business_id', $expense->business_id)
            ->where(function ($q) use ($expense) {
                $q->whereNull('location_id')->orWhere('location_id', $expense->location_id);
            })
            ->where('is_active', 1)
            ->orderByRaw('location_id IS NULL ASC')
            ->orderByDesc('id')
            ->first();
    }

    public function approve(Expense $expense, ?string $note = null): Expense
    {
        return DB::transaction(function () use ($expense, $note) {
            $workflow = $this->activeWorkflowFor($expense);
            $nextLevel = $this->nextPendingLevel($expense, $workflow);

            ApprovalLog::create([
                'business_id' => $expense->business_id,
                'location_id' => $expense->location_id ?? null,
                'expense_id' => $expense->id,
                'workflow_id' => optional($workflow)->id,
                'level_no' => $nextLevel,
                'action' => 'approved',
                'amount' => $expense->total_amount ?? $expense->final_total ?? $expense->amount ?? 0,
                'note' => $note,
                'action_by' => Auth::id(),
                'meta_json' => ['ip' => request()->ip()],
            ]);

            $expense->status = $this->statusAfterLevel($expense, $workflow, $nextLevel);
            $expense->save();

            app(WorkflowService::class)->transition($expense, $expense->status, $note);
            return $expense->fresh();
        });
    }

    public function reject(Expense $expense, ?string $note = null): Expense
    {
        ApprovalLog::create([
            'business_id' => $expense->business_id,
            'location_id' => $expense->location_id ?? null,
            'expense_id' => $expense->id,
            'action' => 'rejected',
            'amount' => $expense->total_amount ?? $expense->final_total ?? $expense->amount ?? 0,
            'note' => $note,
            'action_by' => Auth::id(),
        ]);

        return app(WorkflowService::class)->transition($expense, WorkflowService::REJECTED, $note);
    }

    protected function nextPendingLevel(Expense $expense, ?ApprovalWorkflow $workflow): int
    {
        $last = ApprovalLog::where('expense_id', $expense->id)->where('action', 'approved')->max('level_no');
        return ((int) $last) + 1;
    }

    protected function statusAfterLevel(Expense $expense, ?ApprovalWorkflow $workflow, int $level): string
    {
        $totalLevels = $workflow ? max(1, (int) $workflow->levels()->count()) : 2;
        if ($level >= $totalLevels) {
            return WorkflowService::FINANCE_APPROVED;
        }
        return WorkflowService::MANAGER_APPROVED;
    }
}
