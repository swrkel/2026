<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Balance In Hand for one shift.
 *
 *     Cash Collection
 *   + Customer Payment - Cash
 *   - Cash Expenses
 *   - Cash Purchases
 *   - Cash Deposit
 *   = Balance In Hand
 *
 * Every figure is computed from the entries that carry this shift number.
 * Nothing is stored: a balance written down once is a balance that disagrees
 * with its own workings the moment anything is corrected.
 */
class CashStatusService
{
    public function figures(int $businessId, string $shiftNo, int $shiftId): array
    {
        $collection = $this->cashCollection($shiftId);
        $customerPayments = $this->customerPaymentsCash($businessId, $shiftNo);
        $expenses = $this->cashExpenses($businessId, $shiftNo, $shiftId);
        $purchases = $this->cashPurchases($businessId, $shiftNo, $shiftId);
        $deposits = $this->cashDeposits($businessId, $shiftNo);

        $balance = $collection['total']
            + $customerPayments['total']
            - $expenses['total']
            - $purchases['total']
            - $deposits['total'];

        return [
            'collection' => $collection,
            'customer_payments' => $customerPayments,
            'expenses' => $expenses,
            'purchases' => $purchases,
            'deposits' => $deposits,
            'balance' => round($balance, 2),
        ];
    }

    /**
     * Cash handed over by the operators on this shift.
     *
     * From SW's own table, so this one is keyed on the shift ID rather than the
     * number - it is our record and the relationship is direct.
     */
    protected function cashCollection(int $shiftId): array
    {
        if (! Schema::hasTable('sw_daily_cash')) {
            return $this->empty();
        }

        $rows = DB::table('sw_daily_cash as dc')
            ->leftJoin('pump_operators as po', 'po.id', '=', 'dc.pump_operator_id')
            ->where('dc.sw_shift_id', $shiftId)
            ->orderBy('dc.id')
            ->get([
                'dc.id',
                'dc.collection_form_no as reference',
                'dc.collection_date as date',
                'dc.current_amount as amount',
                'dc.note',
                'po.name as party',
            ]);

        return $this->wrap($rows);
    }

    /**
     * Cash taken from customers during this shift.
     *
     * Restricted to CASH. A card or cheque payment does not put money in the
     * till, so including it would overstate what should physically be there.
     */
    protected function customerPaymentsCash(int $businessId, string $shiftNo): array
    {
        if (! Schema::hasTable('transaction_payments')
            || ! Schema::hasColumn('transaction_payments', 'sw_shift_no')) {
            return $this->empty();
        }

        $rows = DB::table('transaction_payments as tp')
            ->leftJoin('contacts as c', 'c.id', '=', 'tp.payment_for')
            ->where('tp.business_id', $businessId)
            ->where('tp.sw_shift_no', $shiftNo)
            ->where('tp.method', 'cash')
            ->when(Schema::hasColumn('transaction_payments', 'is_return'),
                fn ($q) => $q->where('tp.is_return', 0))
            ->orderBy('tp.id')
            ->get([
                'tp.id',
                'tp.payment_ref_no as reference',
                'tp.paid_on as date',
                'tp.amount',
                'tp.note',
                'c.name as party',
            ]);

        return $this->wrap($rows);
    }

    /**
     * Expenses paid in cash on this shift.
     *
     * An expense is only in the till's arithmetic if it was actually paid from
     * the till, so this joins the payments and takes the cash ones - the
     * expense total itself may include amounts settled another way, or not yet
     * settled at all.
     */
    protected function cashExpenses(int $businessId, string $shiftNo, int $shiftId): array
    {
        $sections = [];

        /*
         | Legacy/core expense transactions.
         |
         | Keep this path because tenants can still create expenses through the
         | host transaction table. Only cash payment rows tied to this exact SW
         | shift belong in Balance In Hand.
        */
        if ($this->canReadTransactionCashForShift()) {
            $rows = DB::table('transactions as t')
                ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
                ->leftJoin('expense_categories as ec', 'ec.id', '=', 't.expense_category_id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'expense')
                ->whereRaw("LOWER(COALESCE(tp.method, '')) = ?", ['cash'])
                ->where(function ($q) use ($shiftNo, $shiftId) {
                    $this->applyTransactionShiftMatch($q, $shiftNo, $shiftId);
                })
                ->when(Schema::hasColumn('transaction_payments', 'is_return'),
                    fn ($q) => $q->where('tp.is_return', 0))
                ->orderBy('t.id')
                ->get([
                    't.id',
                    't.ref_no as reference',
                    't.transaction_date as date',
                    'tp.amount',
                    't.additional_notes as note',
                    'ec.name as party',
                ]);

            $sections[] = $this->wrap($rows);
        }

        /*
         | Expenses New is standalone and does not write a host transactions
         | row. Its cash movement lives in expnew_expense_payments.
         |
         | IS2201 stores the selected SW shift on that payment row. Read the
         | explicit link only; never guess by date because one location may run
         | more than one shift on the same day.
        */
        $sections[] = $this->expensesNewCash($businessId, $shiftNo);

        return $this->mergeSections($sections);
    }

    protected function expensesNewCash(int $businessId, string $shiftNo): array
    {
        if (! Schema::hasTable('expnew_expenses')
            || ! Schema::hasTable('expnew_expense_payments')
            || ! Schema::hasColumn('expnew_expense_payments', 'sw_shift_no')) {
            return $this->empty();
        }

        $query = DB::table('expnew_expense_payments as ep')
            ->join('expnew_expenses as e', 'e.id', '=', 'ep.expense_id')
            ->leftJoin('expnew_categories as c', 'c.id', '=', 'e.category_id')
            ->where('e.business_id', $businessId)
            ->where('ep.business_id', $businessId)
            ->where('ep.sw_shift_no', $shiftNo)
            ->whereRaw("LOWER(COALESCE(ep.method, '')) = ?", ['cash'])
            ->where('ep.amount', '>', 0);

        if (Schema::hasColumn('expnew_expenses', 'status')) {
            $query->whereRaw(
                "LOWER(COALESCE(e.status, '')) NOT IN (?, ?, ?)",
                ['deleted', 'cancelled', 'canceled']
            );
        }

        $rows = $query
            ->orderBy('ep.id')
            ->get([
                'ep.id',
                'e.expense_no as reference',
                'ep.payment_date as date',
                'ep.amount',
                'e.notes as note',
                'c.name as party',
            ]);

        return $this->wrap($rows);
    }

    protected function mergeSections(array $sections): array
    {
        $rows = collect();

        foreach ($sections as $section) {
            if (isset($section['rows']) && $section['rows']->isNotEmpty()) {
                $rows = $rows->concat($section['rows']);
            }
        }

        return $this->wrap($rows->values());
    }

    /** Purchases paid in cash on this shift. Same reasoning as expenses. */
    protected function cashPurchases(int $businessId, string $shiftNo, int $shiftId): array
    {
        if (! $this->canReadTransactionCashForShift()) {
            return $this->empty();
        }

        $rows = DB::table('transactions as t')
            ->join('transaction_payments as tp', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c', 'c.id', '=', 't.contact_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'purchase')
            ->whereRaw("LOWER(COALESCE(tp.method, '')) = ?", ['cash'])
            ->where(function ($q) use ($shiftNo, $shiftId) {
                $this->applyTransactionShiftMatch($q, $shiftNo, $shiftId);
            })
            ->when(Schema::hasColumn('transaction_payments', 'is_return'),
                fn ($q) => $q->where('tp.is_return', 0))
            ->orderBy('t.id')
            ->get([
                't.id',
                't.ref_no as reference',
                't.transaction_date as date',
                'tp.amount',
                't.additional_notes as note',
                'c.name as party',
            ]);

        return $this->wrap($rows);
    }

    protected function applyTransactionShiftMatch($query, string $shiftNo, int $shiftId): void
    {
        $tests = [];

        foreach ([
            ['transactions', 't', 'sw_shift_no', $shiftNo],
            ['transaction_payments', 'tp', 'sw_shift_no', $shiftNo],
            ['transactions', 't', 'shift_number', $shiftNo],
            ['transaction_payments', 'tp', 'shift_number', $shiftNo],
            ['transactions', 't', 'sw_shift_id', $shiftId],
            ['transaction_payments', 'tp', 'sw_shift_id', $shiftId],
        ] as [$table, $alias, $column, $value]) {
            if ($value !== '' && $value !== 0 && Schema::hasColumn($table, $column)) {
                $tests[] = [$alias . '.' . $column, $value];
            }
        }

        if (empty($tests)) {
            $query->whereRaw('1 = 0');
            return;
        }

        foreach ($tests as $i => [$column, $value]) {
            if ($i === 0) {
                $query->where($column, $value);
            } else {
                $query->orWhere($column, $value);
            }
        }
    }

    protected function canReadTransactionCashForShift(): bool
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasTable('transaction_payments')) {
            return false;
        }

        foreach (['sw_shift_no', 'shift_number', 'sw_shift_id'] as $column) {
            if (Schema::hasColumn('transactions', $column)
                || Schema::hasColumn('transaction_payments', $column)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cash banked during this shift.
     *
     * Only the CREDIT leg carries the shift - that is the Cash account paying
     * out. The debit is the bank receiving, and counting both would subtract
     * every deposit twice.
     */
    protected function cashDeposits(int $businessId, string $shiftNo): array
    {
        if (! Schema::hasTable('account_transactions') || ! Schema::hasTable('accounts')) {
            return $this->empty();
        }

        /*
         | IS2269: Finance posts a deposit as a transfer pair. Daily Cash Status
         | must subtract the Cash leg ONCE - never both the Cash and Bank legs.
         |
         | Current SW integration persists the selected Finance Daily Shift No in
         | account_transactions.sw_shift_no. Older Finance builds may already
         | have written the same value to shift_number, so accept either column.
        */
        $hasSwShiftNo = Schema::hasColumn('account_transactions', 'sw_shift_no');
        $hasLegacyShiftNo = Schema::hasColumn('account_transactions', 'shift_number');

        if (! $hasSwShiftNo && ! $hasLegacyShiftNo) {
            return $this->empty();
        }

        $query = DB::table('account_transactions as at')
            ->leftJoin('accounts as a', 'a.id', '=', 'at.account_id')
            ->where('at.business_id', $businessId)
            ->where(function ($q) use ($shiftNo, $hasSwShiftNo, $hasLegacyShiftNo) {
                if ($hasSwShiftNo) {
                    $q->where('at.sw_shift_no', $shiftNo);
                }
                if ($hasLegacyShiftNo) {
                    if ($hasSwShiftNo) {
                        $q->orWhere('at.shift_number', $shiftNo);
                    } else {
                        $q->where('at.shift_number', $shiftNo);
                    }
                }
            })
            // Finance's Cash Deposit form uses the business account named Cash.
            // This deliberately excludes Card Deposit and the receiving Bank leg.
            ->whereRaw('LOWER(TRIM(COALESCE(a.name, ?))) = ?', ['', 'cash']);

        if (Schema::hasColumn('account_transactions', 'sub_type')) {
            $query->whereRaw("LOWER(COALESCE(at.sub_type, '')) = ?", ['deposit']);
        }

        // In Finance Cash Deposit the source Cash leg is the credit row.
        // Keeping that direction makes the report immune to an unrelated
        // transfer/deposit whose receiving leg happens to be the Cash account.
        if (Schema::hasColumn('account_transactions', 'type')) {
            $query->whereRaw("LOWER(COALESCE(at.type, '')) = ?", ['credit']);
        }

        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $query->whereNull('at.deleted_at');
        }

        $hasTransferPair = Schema::hasColumn('account_transactions', 'transfer_transaction_id');
        if ($hasTransferPair) {
            $query->leftJoin('account_transactions as pair', 'pair.id', '=', 'at.transfer_transaction_id')
                ->leftJoin('accounts as pa', 'pa.id', '=', 'pair.account_id');
        }

        $reference = Schema::hasColumn('account_transactions', 'reff_no')
            ? 'at.reff_no as reference'
            : (Schema::hasColumn('account_transactions', 'ref_no')
                ? 'at.ref_no as reference'
                : DB::raw('NULL as reference'));

        $party = $hasTransferPair
            ? DB::raw('COALESCE(pa.name, a.name) as party')
            : 'a.name as party';

        $rows = $query
            ->orderBy('at.id')
            ->get([
                'at.id',
                $reference,
                'at.operation_date as date',
                'at.amount',
                'at.note',
                $party,
            ]);

        return $this->wrap($rows);
    }

    protected function wrap($rows): array
    {
        return [
            'rows' => $rows,
            'total' => round((float) $rows->sum(fn ($r) => (float) $r->amount), 2),
            'count' => $rows->count(),
        ];
    }

    protected function empty(): array
    {
        return ['rows' => collect(), 'total' => 0.0, 'count' => 0];
    }
}
