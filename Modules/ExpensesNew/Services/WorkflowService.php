<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Entities\StatusHistory;
use Modules\ExpensesNew\Entities\ActivityLog;

class WorkflowService
{
    public const DRAFT = 'draft';
    public const SUBMITTED = 'submitted';
    public const MANAGER_APPROVED = 'manager_approved';
    public const FINANCE_APPROVED = 'finance_approved';
    public const PAYMENT_APPROVED = 'payment_approved';
    public const PAID = 'paid';
    public const CLOSED = 'closed';
    public const REJECTED = 'rejected';
    public const RETURNED = 'returned';
    public const CANCELLED = 'cancelled';

    public function submit(Expense $expense, ?string $note = null): Expense
    {
        return $this->transition($expense, self::SUBMITTED, $note);
    }

    public function returnForCorrection(Expense $expense, ?string $note = null): Expense
    {
        return $this->transition($expense, self::RETURNED, $note);
    }

    public function cancel(Expense $expense, ?string $note = null): Expense
    {
        return $this->transition($expense, self::CANCELLED, $note);
    }

    public function markPaid(Expense $expense, ?string $note = null): Expense
    {
        return $this->transition($expense, self::PAID, $note);
    }

    public function close(Expense $expense, ?string $note = null): Expense
    {
        return $this->transition($expense, self::CLOSED, $note);
    }

    public function transition(Expense $expense, string $newStatus, ?string $note = null): Expense
    {
        return DB::transaction(function () use ($expense, $newStatus, $note) {
            $oldStatus = $expense->status;
            $expense->status = $newStatus;
            $expense->save();

            StatusHistory::create([
                'business_id' => $expense->business_id,
                'location_id' => $expense->location_id ?? null,
                'expense_id' => $expense->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => $note,
                'changed_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'business_id' => $expense->business_id,
                'location_id' => $expense->location_id ?? null,
                'subject_type' => Expense::class,
                'subject_id' => $expense->id,
                'event' => 'status_changed',
                'description' => 'Expense status changed from '.$oldStatus.' to '.$newStatus,
                'before_json' => ['status' => $oldStatus],
                'after_json' => ['status' => $newStatus],
                'created_by' => Auth::id(),
            ]);

            return $expense->fresh();
        });
    }
}
