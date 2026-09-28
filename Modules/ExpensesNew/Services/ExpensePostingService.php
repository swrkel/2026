<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ExpensesNew\Entities\Expense;
use Modules\ExpensesNew\Entities\ExpensePayment;

class ExpensePostingService
{
    public function __construct(
        private readonly FinanceAccountBookPostingService $accountBooks
    ) {
    }

    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data): Expense {
            $data = $this->normalizeAmounts($data);
            $expense = Expense::create($data);
            $this->syncPayments($expense, $data);
            $this->accountBooks->sync($expense->fresh(['category', 'payee', 'account', 'payments']));

            return $expense->fresh(['payments']);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data): Expense {
            $data = $this->normalizeAmounts($data);
            $expense->update($data);
            $this->syncPayments($expense, $data);
            $this->accountBooks->sync($expense->fresh(['category', 'payee', 'account', 'payments']));

            return $expense->fresh(['payments']);
        });
    }

    protected function normalizeAmounts(array $data): array
    {
        $paid = round((float) ($data['paid_amount'] ?? 0), 4);
        $total = round((float) ($data['total_amount'] ?? $paid), 4);
        $data['total_amount'] = $total;
        $data['paid_amount'] = min($paid, $total);
        $data['due_amount'] = round($total - $data['paid_amount'], 4);
        $data['balance_amount'] = $data['due_amount'];
        $data['payment_status'] = $data['paid_amount'] <= 0
            ? 'due'
            : ($data['due_amount'] <= 0 ? 'paid' : 'partial');
        $data['status'] = $data['status'] ?? 'active';

        return $data;
    }

    protected function syncPayments(Expense $expense, array $data): void
    {
        ExpensePayment::where('expense_id', $expense->id)->delete();
        if ((float) $expense->paid_amount <= 0) {
            return;
        }

        $payment = [
            'business_id' => $expense->business_id,
            'expense_id' => $expense->id,
            'payment_date' => $expense->expense_date,
            'method' => $data['payment_method'] ?? 'cash',
            'amount' => $expense->paid_amount,
            'reference_no' => $data['reference_no'] ?? null,
            'cheque_no' => $data['cheque_no'] ?? null,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'card_no' => $data['card_no'] ?? null,
            'created_by' => $data['updated_by'] ?? $data['created_by'] ?? auth()->id(),
        ];

        // IS2201: keep the explicit SW shift on the payment movement. Do not
        // put it on expnew_expenses; one expense payment is the till movement.
        if (Schema::hasColumn('expnew_expense_payments', 'sw_shift_no')) {
            $payment['sw_shift_no'] = $data['sw_shift_no'] ?? null;
        }

        ExpensePayment::create($payment);
    }
}
