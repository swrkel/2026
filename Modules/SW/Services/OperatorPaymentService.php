<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Recover Shortage and Pay Excess.
 *
 * THE EXCEPTION TO THE RULE
 *
 * Everything else the SW module records during a shift only records - the
 * settlement is where figures become accounting. These two are different: real
 * cash changes hands the moment the form is saved, and the shortage being
 * recovered may belong to a settlement closed weeks ago. So these post to
 * Finance immediately.
 *
 * THE ENTRIES
 *
 *   Recover Shortage - the operator hands money back
 *       DEBIT   the selected account   (cash or bank - where the money lands)
 *       CREDIT  Accounts Receivable    (the operator's debt is cleared)
 *
 *   Pay Excess - the business hands money to the operator
 *       DEBIT   Accounts Receivable
 *       CREDIT  the selected account   (money leaves it)
 *
 * Both are wrapped in a transaction. A half-written double entry is worse than
 * no entry at all: it leaves the books out of balance with nothing to show why.
 */
class OperatorPaymentService
{
    /**
     * @return array{success: bool, msg: string}
     */
    public function record(array $data, int $businessId, int $userId): array
    {
        $type = $data['type'] === 'excess' ? 'excess' : 'shortage';
        // account_transactions.amount is decimal(22,6); rounding to 4 here
        // matches every other money value in this module and loses nothing.
        $amount = round((float) $data['amount'], 4);

        if ($amount <= 0) {
            return ['success' => false, 'msg' => 'The amount must be more than zero.'];
        }

        try {
            DB::beginTransaction();

            $paymentId = $this->writePaymentRow($data, $businessId, $userId, $type, $amount);
            $this->postToFinance($data, $businessId, $userId, $type, $amount, $paymentId);
            $this->updateOperatorBalance($data, $type, $amount);
            $this->markDiscrepancySettled($data, $amount);

            DB::commit();

            return [
                'success' => true,
                'msg' => $type === 'shortage'
                    ? 'Shortage recovered and posted.'
                    : 'Excess paid and posted.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();

            return ['success' => false, 'msg' => 'Nothing was saved: ' . $e->getMessage()];
        }
    }

    /**
     * The payment row, which is what the Pumper Excess / Shortage Payments tab
     * lists.
     *
     * net_amount is written as a proper decimal. payment_amount on that table is
     * varchar(191) - a money value as text - and is filled only for the benefit
     * of older code that still reads it.
     */
    protected function writePaymentRow(array $data, int $businessId, int $userId, string $type, float $amount): int
    {
        $row = [
            'business_id' => $businessId,
            'pump_operator_id' => (int) $data['pump_operator_id'],
            'payment_type' => $type,
            'payment_amount' => (string) $amount,
            'date_and_time' => now(),
            'note' => $data['note'] ?? null,
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([
            'net_amount' => $amount,
            'gross_amount' => $amount,
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'reference_no' => $data['reference_no'] ?? null,
            'shift_number' => $data['sw_shift_no'] ?? null,
            'source_type' => 'sw_operator_payment',
        ] as $col => $val) {
            if (Schema::hasColumn('pump_operator_payments', $col)) {
                $row[$col] = $val;
            }
        }

        return (int) DB::table('pump_operator_payments')->insertGetId($row);
    }

    /**
     * The double entry, through FINANCE.
     *
     * account_transactions is where Finance keeps its ledger. Two rows, equal
     * and opposite, written together or not at all.
     */
    protected function postToFinance(array $data, int $businessId, int $userId, string $type, float $amount, int $paymentId): void
    {
        if (! Schema::hasTable('account_transactions')) {
            throw new \RuntimeException('Finance is not available on this install.');
        }

        $accountId = (int) ($data['account_id'] ?? 0);
        if ($accountId <= 0) {
            throw new \RuntimeException('Choose the account the money moves through.');
        }

        $receivableId = $this->receivableAccountId($businessId);
        if ($receivableId <= 0) {
            throw new \RuntimeException(
                'No Accounts Receivable account is configured for this business.'
            );
        }

        // Recovering a shortage brings money IN to the chosen account.
        // Paying an excess takes money OUT of it.
        $moneyIn = ($type === 'shortage');

        /*
         | sub_type is deliberately NOT set.
         |
         | It is an enum on this table - opening_balance, fund_transfer,
         | deposit, and so on - and writing a value outside that list either
         | fails or silently stores an empty string, depending on the server's
         | SQL mode. Neither is acceptable for a ledger entry.
         |
         | txnType is a plain varchar and carries the same meaning safely, and
         | source_pump_operator_payment_id links the entry back to the payment
         | that caused it - a column this table already has for exactly this.
        */
        $base = [
            'business_id' => $businessId,
            'created_by' => $userId,
            'operation_date' => $data['transaction_date'] ?? now()->toDateString(),
            'txnType' => $type === 'shortage' ? 'sw_shortage_recovery' : 'sw_excess_payment',
            'note' => $data['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach ([
            ['account_id' => $accountId,     'type' => $moneyIn ? 'credit' : 'debit'],
            ['account_id' => $receivableId,  'type' => $moneyIn ? 'debit' : 'credit'],
        ] as $leg) {
            $row = $base + [
                'account_id' => $leg['account_id'],
                'type' => $leg['type'],
                'amount' => $amount,
            ];

            foreach ([
                'sw_shift_no' => $data['sw_shift_no'] ?? null,
                'reff_no' => $data['reference_no'] ?? null,
                'location_id' => $data['location_id'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'source_pump_operator_payment_id' => $paymentId,
            ] as $col => $val) {
                if ($val !== null && Schema::hasColumn('account_transactions', $col)) {
                    $row[$col] = $val;
                }
            }

            DB::table('account_transactions')->insert($row);
        }
    }

    /**
     * The operator's running short and excess balances.
     *
     * Reduced rather than zeroed: a partial recovery should leave the remainder
     * outstanding, not clear the whole debt. Clamped at zero so an over-recovery
     * cannot push the balance negative.
     */
    protected function updateOperatorBalance(array $data, string $type, float $amount): void
    {
        $column = $type === 'shortage' ? 'short_amount' : 'excess_amount';

        if (! Schema::hasColumn('pump_operators', $column)) {
            return;
        }

        DB::table('pump_operators')
            ->where('id', (int) $data['pump_operator_id'])
            ->update([
                $column => DB::raw("GREATEST(COALESCE($column, 0) - " . $amount . ", 0)"),
                'updated_at' => now(),
            ]);
    }

    /**
     * If the form named a recorded discrepancy, note how much of it is settled.
     * A cache on that row, so the tab can show what is outstanding without
     * re-summing payments.
     */
    protected function markDiscrepancySettled(array $data, float $amount): void
    {
        $id = (int) ($data['sw_daily_shortage_excess_id'] ?? 0);

        if ($id <= 0 || ! Schema::hasTable('sw_daily_shortage_excess')) {
            return;
        }

        DB::table('sw_daily_shortage_excess')
            ->where('id', $id)
            ->update([
                'settled_amount' => DB::raw('COALESCE(settled_amount, 0) + ' . $amount),
                'updated_at' => now(),
            ]);
    }

    /**
     * The business's Accounts Receivable account, from FINANCE.
     *
     * Matched on the account GROUP named "Account Receivable" - the same group
     * the Payment Options screen maps a credit method to. Falls back to an
     * account of that name if the group is absent.
     */
    protected function receivableAccountId(int $businessId): int
    {
        if (! Schema::hasTable('accounts')) {
            return 0;
        }

        if (Schema::hasTable('account_groups') && Schema::hasColumn('accounts', 'asset_type')) {
            $id = DB::table('accounts')
                ->join('account_groups', 'account_groups.id', '=', 'accounts.asset_type')
                ->where('accounts.business_id', $businessId)
                ->where('account_groups.business_id', $businessId)
                ->where('account_groups.name', 'like', '%Account%Receivable%')
                ->when(Schema::hasColumn('accounts', 'deleted_at'),
                    fn ($q) => $q->whereNull('accounts.deleted_at'))
                ->value('accounts.id');

            if ($id) {
                return (int) $id;
            }
        }

        return (int) DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('name', 'like', '%Receivable%')
            ->when(Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('deleted_at'))
            ->value('id');
    }
}
