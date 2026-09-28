<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Payments summary bar — 8048.
 *
 *   Current Short  ·  Current Excess  ·  Daily Cash  ·  Daily Credit Sales
 *   ·  Commission Amount
 *
 * The first two are the operator's running balances; the rest are totals for
 * the shifts being settled.
 */
class PaymentSummaryService
{
    public function summary(int $businessId, int $operatorId, array $shiftIds): array
    {
        return [
            'current_short' => $this->operatorBalance($businessId, $operatorId, 'short_amount'),
            'current_excess' => $this->operatorBalance($businessId, $operatorId, 'excess_amount'),
            'daily_cash' => $this->shiftTotal('sw_daily_cash', $shiftIds, $operatorId, 'current_amount'),
            'daily_credit_sales' => $this->shiftTotal('sw_daily_credit_sales', $shiftIds, $operatorId, 'amount'),
            'commission' => $this->unpaidCommission($businessId, $operatorId),
            'recorded_payments' => $this->recordedPayments($businessId, $operatorId, $shiftIds),
        ];
    }

    /**
     * Rows entered through SW Payments for the selected closed shifts/operator.
     *
     * These are returned for display in the settlement's Payments table. The
     * daily source rows remain untouched; settlement save creates its own
     * authoritative collection rows.
     */
    protected function recordedPayments(int $businessId, int $operatorId, array $shiftIds): array
    {
        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));

        if (empty($shiftIds) || $operatorId <= 0 || ! Schema::hasTable('sw_shifts')) {
            return [];
        }

        $rows = [];

        // Cash.
        if (Schema::hasTable('sw_daily_cash')
            && Schema::hasColumn('sw_daily_cash', 'sw_shift_id')
            && Schema::hasColumn('sw_daily_cash', 'pump_operator_id')) {
            $amountColumn = Schema::hasColumn('sw_daily_cash', 'current_amount')
                ? 'current_amount'
                : (Schema::hasColumn('sw_daily_cash', 'amount') ? 'amount' : null);

            if ($amountColumn) {
                $query = DB::table('sw_daily_cash as d')
                    ->join('sw_shifts as s', 's.id', '=', 'd.sw_shift_id')
                    ->where('s.business_id', $businessId)
                    ->whereIn('d.sw_shift_id', $shiftIds)
                    ->where('d.pump_operator_id', $operatorId)
                    ->orderBy('d.id');

                foreach ($query->get(['d.*']) as $r) {
                    $rows[] = [
                        'source_key' => 'cash:' . $r->id,
                        'payment_method' => 'cash',
                        'contact_id' => null,
                        'customer_name' => '',
                        'account_id' => null,
                        'account_name' => '',
                        'amount' => round((float) ($r->{$amountColumn} ?? 0), 4),
                        'reference' => (string) ($r->collection_form_no ?? ''),
                        'note' => (string) ($r->note ?? ''),
                    ];
                }
            }
        }

        // Cards.
        if (Schema::hasTable('sw_daily_cards')
            && Schema::hasColumn('sw_daily_cards', 'sw_shift_id')
            && Schema::hasColumn('sw_daily_cards', 'pump_operator_id')
            && Schema::hasColumn('sw_daily_cards', 'amount')) {
            $query = DB::table('sw_daily_cards as d')
                ->join('sw_shifts as s', 's.id', '=', 'd.sw_shift_id')
                ->where('s.business_id', $businessId)
                ->whereIn('d.sw_shift_id', $shiftIds)
                ->where('d.pump_operator_id', $operatorId)
                ->orderBy('d.id');

            if (Schema::hasTable('contacts') && Schema::hasColumn('sw_daily_cards', 'contact_id')) {
                $query->leftJoin('contacts as c', 'c.id', '=', 'd.contact_id');
            }
            if (Schema::hasTable('accounts') && Schema::hasColumn('sw_daily_cards', 'card_type_account_id')) {
                $query->leftJoin('accounts as a', 'a.id', '=', 'd.card_type_account_id');
            }

            $select = ['d.*'];
            if (Schema::hasTable('contacts') && Schema::hasColumn('sw_daily_cards', 'contact_id')) {
                $select[] = 'c.name as customer_name';
            }
            if (Schema::hasTable('accounts') && Schema::hasColumn('sw_daily_cards', 'card_type_account_id')) {
                $select[] = 'a.name as account_name';
            }

            foreach ($query->get($select) as $r) {
                $rows[] = [
                    'source_key' => 'card:' . $r->id,
                    'payment_method' => 'card',
                    'contact_id' => $r->contact_id ?? null,
                    'customer_name' => (string) ($r->customer_name ?? ''),
                    'account_id' => $r->card_type_account_id ?? null,
                    'account_name' => (string) ($r->account_name ?? ''),
                    'amount' => round((float) ($r->amount ?? 0), 4),
                    'reference' => (string) (($r->slip_no ?? null) ?: ($r->collection_form_no ?? '')),
                    'note' => (string) ($r->note ?? ''),
                ];
            }
        }

        // Cheques. Bank is free text in the Daily Cheques table, not a Finance account id.
        if (Schema::hasTable('sw_daily_cheques')
            && Schema::hasColumn('sw_daily_cheques', 'sw_shift_id')
            && Schema::hasColumn('sw_daily_cheques', 'pump_operator_id')
            && Schema::hasColumn('sw_daily_cheques', 'amount')) {
            $query = DB::table('sw_daily_cheques as d')
                ->join('sw_shifts as s', 's.id', '=', 'd.sw_shift_id')
                ->where('s.business_id', $businessId)
                ->whereIn('d.sw_shift_id', $shiftIds)
                ->where('d.pump_operator_id', $operatorId)
                ->orderBy('d.id');

            if (Schema::hasTable('contacts') && Schema::hasColumn('sw_daily_cheques', 'contact_id')) {
                $query->leftJoin('contacts as c', 'c.id', '=', 'd.contact_id');
            }

            $select = ['d.*'];
            if (Schema::hasTable('contacts') && Schema::hasColumn('sw_daily_cheques', 'contact_id')) {
                $select[] = 'c.name as customer_name';
            }

            foreach ($query->get($select) as $r) {
                $rows[] = [
                    'source_key' => 'cheque:' . $r->id,
                    'payment_method' => 'cheque',
                    'contact_id' => $r->contact_id ?? null,
                    'customer_name' => (string) ($r->customer_name ?? ''),
                    'account_id' => null,
                    'account_name' => (string) ($r->bank ?? ''),
                    'amount' => round((float) ($r->amount ?? 0), 4),
                    'reference' => (string) (($r->cheque_no ?? null) ?: ($r->collection_form_no ?? '')),
                    'note' => (string) ($r->note ?? ''),
                ];
            }
        }

        /*
         * IS2245/IS2240: Daily Credit Sales are loaded through the dedicated
         * detailed Credit Sales panel (product/qty/price/discount/customer).
         * Do NOT also inject them into the generic Payments rows here. Doing so
         * makes one credit sale appear twice in Total Paid and posts a redundant
         * sw_collections credit_sale row on save. The summary figure above still
         * reports daily_credit_sales for the operator header.
         */

        return array_values(array_filter($rows, static fn (array $row): bool => (float) $row['amount'] > 0));
    }

    /**
     * The operator's running shortage or excess.
     *
     * 8048: "If any current shortage for the selected operator, need to show."
     * This is what the operator already owes or is owed before this settlement
     * touches anything.
     */
    protected function operatorBalance(int $businessId, int $operatorId, string $column): float
    {
        if ($operatorId <= 0 || ! Schema::hasColumn('pump_operators', $column)) {
            return 0.0;
        }

        return round((float) (DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', $operatorId)
            ->value($column) ?? 0), 2);
    }

    /**
     * A daily tab's total for these shifts and this operator.
     *
     * BOTH must match - 8043 is explicit that the shift number and the operator
     * have to agree. A shift may have had several operators, and showing
     * another's cash against this settlement would misstate what this person
     * handed over.
     */
    protected function shiftTotal(string $table, array $shiftIds, int $operatorId, string $column): float
    {
        if (empty($shiftIds) || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        return round((float) (DB::table($table)
            ->whereIn('sw_shift_id', $shiftIds)
            ->when($operatorId > 0 && Schema::hasColumn($table, 'pump_operator_id'),
                fn ($q) => $q->where('pump_operator_id', $operatorId))
            ->sum($column) ?? 0), 2);
    }

    /**
     * Commission earned and not yet paid — 8048.
     *
     * Only the unpaid part: commission already settled would otherwise be
     * offered a second time.
     */
    protected function unpaidCommission(int $businessId, int $operatorId): float
    {
        if ($operatorId <= 0 || ! Schema::hasTable('pump_operator_commission')) {
            return 0.0;
        }

        $q = DB::table('pump_operator_commission')
            ->where('pump_operator_id', $operatorId);

        if (Schema::hasColumn('pump_operator_commission', 'business_id')) {
            $q->where('business_id', $businessId);
        }

        if (Schema::hasColumn('pump_operator_commission', 'is_paid')) {
            $q->where('is_paid', 0);
        }

        return round((float) ($q->sum('amount') ?? 0), 2);
    }
}
