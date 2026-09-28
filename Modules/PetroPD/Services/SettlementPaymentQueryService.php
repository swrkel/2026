<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical read facade for PetroPD Pumper Dashboard payments.
 *
 * Financial rules:
 * - pump_operator_payments.id is the only payment identity.
 * - Outer queries always keep one row per master payment.
 * - Supporting tables supply metadata only through pump_payment_id.
 * - Legacy metadata is exposed only when business/operator/Shift/form resolves
 *   to exactly one supporting row; ambiguous legacy rows return NULL metadata.
 */
class SettlementPaymentQueryService
{
    public const RETURN_SHAPE = [
        'pump_payment_id',
        'business_id',
        'pump_operator_id',
        'shift_id',
        'settlement_id',
        'settlement_no',
        'collection_form_no',
        'payment_type',
        'payment_amount',
        'gross_amount',
        'discount_amount',
        'net_amount',
        'customer_id',
        'reference_no',
        'transaction_date',
        'source_type',
        'source_id',
        'is_used',
        'parent_id',
        'created_at',
        'updated_at',
    ];

    /** @return Collection<int,array<string,mixed>> */
    public function paymentsForShift(int $businessId, int $shiftId): Collection
    {
        return $this->base()
            ->where('pop.business_id', $businessId)
            ->where('pop.shift_id', $shiftId)
            ->orderBy('pop.id')
            ->get()
            ->map([$this, 'shape'])
            ->values();
    }

    /** @return Collection<int,array<string,mixed>> */
    public function paymentsForSettlement(int $businessId, int $settlementId): Collection
    {
        return $this->base()
            ->where('pop.business_id', $businessId)
            ->where(function ($query) use ($settlementId) {
                $query->where('pop.settlement_no', (string) $settlementId)
                    ->orWhere('pop.settlement_no', $settlementId);
            })
            ->orderBy('pop.id')
            ->get()
            ->map([$this, 'shape'])
            ->values();
    }

    /** @return Collection<int,array<string,mixed>> */
    public function paymentsForOperator(int $businessId, int $operatorId, ?int $shiftId = null): Collection
    {
        $query = $this->base()
            ->where('pop.business_id', $businessId)
            ->where('pop.pump_operator_id', $operatorId);

        if ($shiftId !== null) {
            $query->where('pop.shift_id', $shiftId);
        }

        return $query->orderBy('pop.id')
            ->get()
            ->map([$this, 'shape'])
            ->values();
    }

    /** @return array<string,mixed>|null */
    public function paymentByPumpPaymentId(int $pumpPaymentId): ?array
    {
        $row = $this->base()->where('pop.id', $pumpPaymentId)->first();

        return $row ? $this->shape($row) : null;
    }

    /**
     * Safe server-side Payment Summary read model.
     *
     * The aliases intentionally retain the legacy controller names (scsp, dc,
     * pump_operator_assignments, payment_settlements) so existing filters and
     * select expressions can be moved without changing their public response.
     */
    public function paymentSummaryBaseQuery(
        int $businessId,
        bool $hasCardMetaColumns = false,
        bool $includeCheque = false
    ): Builder {
        $query = DB::table('pump_operator_payments')
            ->leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('users as edited_user', 'pump_operator_payments.edited_by', '=', 'edited_user.id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'pump_operators.location_id');

        if (Schema::hasTable('pump_operator_assignments')) {
            $query->leftJoinSub($this->assignmentMetadataSubquery($businessId), 'pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.business_id', '=', 'pump_operator_payments.business_id')
                    ->on('pump_operator_assignments.pump_operator_id', '=', 'pump_operator_payments.pump_operator_id')
                    ->on('pump_operator_assignments.shift_id', '=', 'pump_operator_payments.shift_id');
            });
        }

        if (Schema::hasTable('settlements')) {
            $query->leftJoinSub($this->settlementMetadataSubquery($businessId), 'payment_settlements', function ($join) {
                $join->on('payment_settlements.business_id', '=', 'pump_operator_payments.business_id')
                    ->whereRaw('payment_settlements.settlement_ref = CAST(pump_operator_payments.settlement_no AS CHAR)');
            });
        }

        if (Schema::hasTable('settlement_credit_sale_payments')) {
            $query->leftJoinSub($this->creditMetadataSubquery($businessId), 'scsp', function ($join) {
                $join->on('scsp.business_id', '=', 'pump_operator_payments.business_id')
                    ->on('scsp.pump_payment_id', '=', 'pump_operator_payments.id');
            });

            if (Schema::hasTable('daily_vouchers') && Schema::hasColumn('settlement_credit_sale_payments', 'daily_voucher_id')) {
                $query->leftJoin('daily_vouchers as dv', 'dv.id', '=', 'scsp.daily_voucher_id');
            }

            if (Schema::hasTable('contacts')) {
                $query->leftJoin('contacts as contacts_credit', 'contacts_credit.id', '=', 'scsp.customer_id');
            }
        }

        if (Schema::hasTable('daily_cards')) {
            $query->leftJoinSub($this->cardMetadataSubquery($businessId), 'dc', function ($join) {
                $join->on('dc.pump_payment_id', '=', 'pump_operator_payments.id');
            });
        }

        if (Schema::hasTable('contacts')) {
            if ($hasCardMetaColumns && Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                $query->leftJoin('contacts as contacts_card', 'contacts_card.id', '=', 'pump_operator_payments.customer_id');
            } else {
                $query->leftJoin('contacts as contacts_card', 'contacts_card.id', '=', 'dc.customer_id');
            }
            $query->leftJoin('contacts as contacts_card_legacy', 'contacts_card_legacy.id', '=', 'dc.customer_id');
        }

        if ($includeCheque && Schema::hasTable('daily_cheque_payments')) {
            $query->leftJoinSub($this->chequeMetadataSubquery($businessId), 'dcp', function ($join) {
                $join->on('dcp.pump_payment_id', '=', 'pump_operator_payments.id');
            });

            if (Schema::hasTable('contacts')) {
                $query->leftJoin('contacts as contacts_cheque', 'contacts_cheque.id', '=', 'dcp.customer_id');
            }
        }

        return $query->where('pump_operator_payments.business_id', $businessId);
    }

    /**
     * Master-only totals. No supporting table is joined, so a payment can never
     * be multiplied by card, voucher, product-line or pump-assignment rows.
     *
     * @return array{cash:float,card:float,cheque:float,credit:float,other:float,shortage:float,excess:float,shortage_excess:float,total:float,total_paid:float,count:int}
     */
    public function totalsForScope(
        int $businessId,
        ?int $operatorId = null,
        array $shiftIds = [],
        ?string $startDateTime = null,
        ?string $endDateTime = null
    ): array {
        $shiftIds = array_values(array_unique(array_filter(array_map('intval', $shiftIds))));

        $query = DB::table('pump_operator_payments')
            ->where('business_id', $businessId);

        if ($operatorId !== null && $operatorId > 0) {
            $query->where('pump_operator_id', $operatorId);
        }
        if (! empty($shiftIds)) {
            $query->whereIn('shift_id', $shiftIds);
        }
        if ($startDateTime !== null && $endDateTime !== null) {
            $query->whereBetween(DB::raw('COALESCE(date_and_time, created_at)'), [$startDateTime, $endDateTime]);
        }

        $rows = $query->select([
            'id',
            DB::raw('LOWER(payment_type) as payment_type_key'),
            DB::raw($this->authoritativeAmountSql() . ' as authoritative_amount'),
        ])->orderBy('id')->get()->unique('id')->values();

        $totals = [
            'cash' => 0.0,
            'card' => 0.0,
            'cheque' => 0.0,
            'credit' => 0.0,
            'other' => 0.0,
            'shortage' => 0.0,
            'excess' => 0.0,
            'shortage_excess' => 0.0,
            'total' => 0.0,
            'total_paid' => 0.0,
            'count' => $rows->count(),
        ];

        foreach ($rows as $row) {
            $type = $this->normalizeType((string) $row->payment_type_key);
            $amount = (float) $row->authoritative_amount;

            if (array_key_exists($type, $totals)) {
                $totals[$type] += $amount;
            }
            $totals['total'] += $amount;
        }

        $totals['shortage_excess'] = $totals['shortage'] + $totals['excess'];
        $totals['total_paid'] = $totals['cash'] + $totals['card'] + $totals['cheque']
            + $totals['credit'] + $totals['other'] + $totals['shortage'] + $totals['excess'];

        foreach ($totals as $key => $value) {
            if ($key !== 'count') {
                $totals[$key] = round((float) $value, 4);
            }
        }

        return $totals;
    }

    private function base(): Builder
    {
        $columns = [
            'pop.id as pump_payment_id',
            'pop.business_id',
            'pop.pump_operator_id',
            'pop.shift_id',
            'pop.settlement_no',
            'pop.collection_form_no',
            'pop.payment_type',
            'pop.payment_amount',
            'pop.is_used',
            'pop.parent_id',
            'pop.created_at',
            'pop.updated_at',
        ];

        foreach ([
            'gross_amount', 'discount_amount', 'net_amount', 'customer_id',
            'reference_no', 'transaction_date', 'source_type', 'source_id',
        ] as $column) {
            if (Schema::hasColumn('pump_operator_payments', $column)) {
                $columns[] = 'pop.' . $column;
            }
        }

        return DB::table('pump_operator_payments as pop')->select($columns);
    }

    public function shape($row): array
    {
        $settlementNo = $row->settlement_no ?? null;
        $settlementId = is_string($settlementNo) && ctype_digit($settlementNo)
            ? (int) $settlementNo
            : null;

        return [
            'pump_payment_id' => (int) $row->pump_payment_id,
            'business_id' => (int) $row->business_id,
            'pump_operator_id' => $row->pump_operator_id !== null ? (int) $row->pump_operator_id : null,
            'shift_id' => $row->shift_id !== null ? (int) $row->shift_id : null,
            'settlement_id' => $settlementId,
            'settlement_no' => $settlementNo,
            'collection_form_no' => $row->collection_form_no ?? null,
            'payment_type' => $row->payment_type,
            'payment_amount' => (string) $row->payment_amount,
            'gross_amount' => isset($row->gross_amount) ? (string) $row->gross_amount : null,
            'discount_amount' => isset($row->discount_amount) ? (string) $row->discount_amount : null,
            'net_amount' => isset($row->net_amount) ? (string) $row->net_amount : null,
            'customer_id' => isset($row->customer_id) ? (int) $row->customer_id : null,
            'reference_no' => $row->reference_no ?? null,
            'transaction_date' => $row->transaction_date ?? null,
            'source_type' => $row->source_type ?? null,
            'source_id' => isset($row->source_id) ? (int) $row->source_id : null,
            'is_used' => (int) ($row->is_used ?? 0),
            'parent_id' => $row->parent_id !== null ? (int) $row->parent_id : null,
            'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
            'updated_at' => $row->updated_at !== null ? (string) $row->updated_at : null,
        ];
    }

    private function assignmentMetadataSubquery(int $businessId): Builder
    {
        return DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->select([
                'business_id',
                'pump_operator_id',
                'shift_id',
                DB::raw('MAX(CAST(shift_number AS UNSIGNED)) as shift_number'),
                DB::raw('MAX(id) as assignment_id'),
            ])
            ->groupBy('business_id', 'pump_operator_id', 'shift_id');
    }

    private function creditMetadataSubquery(int $businessId): Builder
    {
        // A single credit invoice can legitimately contain several product-line
        // rows. Keep one outer row per Pump Operator Payment and expose metadata
        // only when every line agrees on the same value. This prevents product
        // lines multiplying the master amount without hiding valid invoices.
        $select = [
            'business_id',
            'pump_payment_id',
            DB::raw('MIN(id) as id'),
            DB::raw('COUNT(*) as detail_count'),
        ];

        foreach ([
            'customer_id',
            'order_number',
            'daily_voucher_id',
            'customer_reference',
            'order_date',
            'settlement_no',
        ] as $column) {
            if (! Schema::hasColumn('settlement_credit_sale_payments', $column)) {
                $select[] = DB::raw('NULL as ' . $column);
                continue;
            }

            $select[] = DB::raw(
                'CASE WHEN COUNT(DISTINCT ' . $column . ') <= 1 '
                . 'THEN MAX(' . $column . ') ELSE NULL END as ' . $column
            );
        }

        return DB::table('settlement_credit_sale_payments')
            ->where('business_id', $businessId)
            ->whereNotNull('pump_payment_id')
            ->where('pump_payment_id', '>', 0)
            ->select($select)
            ->groupBy('business_id', 'pump_payment_id');
    }

    private function cardMetadataSubquery(int $businessId): Builder
    {
        $hasDirectLink = Schema::hasColumn('daily_cards', 'pump_payment_id');
        $hasShift = Schema::hasColumn('daily_cards', 'shift_id');
        $hasOperator = Schema::hasColumn('daily_cards', 'pump_operator_id');

        $direct = DB::table('daily_cards')
            ->where('business_id', $businessId)
            ->when($hasDirectLink, fn ($query) => $query->whereNotNull('pump_payment_id')->where('pump_payment_id', '>', 0))
            ->when(! $hasDirectLink, fn ($query) => $query->whereRaw('1 = 0'))
            ->select([
                'business_id',
                DB::raw($hasDirectLink ? 'pump_payment_id' : 'NULL as pump_payment_id'),
                DB::raw('CASE WHEN COUNT(*) = 1 THEN MIN(id) ELSE NULL END as id'),
                DB::raw('CASE WHEN COUNT(*) = 1 THEN MIN(customer_id) ELSE NULL END as customer_id'),
                DB::raw("CASE WHEN COUNT(*) = 1 THEN NULLIF(TRIM(MIN(slip_no)), '') ELSE NULL END as slip_no"),
                DB::raw('CASE WHEN COUNT(*) = 1 THEN MIN(amount) ELSE NULL END as amount'),
                DB::raw('COUNT(*) as match_count'),
            ])
            ->groupBy('business_id', $hasDirectLink ? 'pump_payment_id' : DB::raw('NULL'));

        /*
         | PETROPD-SLIP-NO-20260821
         |
         | Keep the historical shift-aware match first. Older PetroPD-created
         | daily_cards can contain shift_id and this is the most specific link.
         */
        $legacy = DB::table('daily_cards')
            ->where('business_id', $businessId)
            ->when($hasDirectLink, fn ($query) => $query->whereNull('pump_payment_id'))
            ->when(! $hasShift || ! $hasOperator, fn ($query) => $query->whereRaw('1 = 0'))
            ->select([
                'business_id',
                DB::raw($hasOperator ? 'pump_operator_id' : 'NULL as pump_operator_id'),
                DB::raw($hasShift ? 'shift_id' : 'NULL as shift_id'),
                'collection_no',
                DB::raw('MIN(id) as id'),
                DB::raw('MIN(customer_id) as customer_id'),
                DB::raw("NULLIF(TRIM(MIN(slip_no)), '') as slip_no"),
                DB::raw('MIN(amount) as amount'),
                DB::raw('COUNT(*) as match_count'),
            ])
            ->groupBy('business_id', $hasOperator ? 'pump_operator_id' : DB::raw('NULL'), $hasShift ? 'shift_id' : DB::raw('NULL'), 'collection_no')
            ->havingRaw('COUNT(*) = 1');

        /*
         | Pumper Dashboard parity fallback.
         |
         | The working Pumper Dashboard card flow saves daily_cards with
         | business_id + pump_operator_id + collection_no + amount, but does not
         | reliably save daily_cards.shift_id. Requiring shift_id therefore made
         | PetroPD lose the Slip No even while Pumper Dashboard showed it.
         |
         | Grouping and HAVING COUNT(*) = 1 is deliberate: only one unambiguous
         | card can be attached to a pump payment. If duplicate cards share the
         | same identifying values, this fallback returns no metadata rather than
         | displaying the wrong customer's slip number.
         */
        $dashboardFallback = DB::table('daily_cards')
            ->where('business_id', $businessId)
            ->when($hasDirectLink, fn ($query) => $query->whereNull('pump_payment_id'))
            ->when(! $hasOperator, fn ($query) => $query->whereRaw('1 = 0'))
            ->select([
                'business_id',
                DB::raw($hasOperator ? 'pump_operator_id' : 'NULL as pump_operator_id'),
                'collection_no',
                'amount',
                DB::raw('MIN(id) as id'),
                DB::raw('MIN(customer_id) as customer_id'),
                DB::raw("NULLIF(TRIM(MIN(slip_no)), '') as slip_no"),
                DB::raw('COUNT(*) as match_count'),
            ])
            ->groupBy('business_id', $hasOperator ? 'pump_operator_id' : DB::raw('NULL'), 'collection_no', 'amount')
            ->havingRaw('COUNT(*) = 1');

        return DB::table('pump_operator_payments as card_pop')
            ->leftJoinSub($direct, 'direct_card', function ($join) {
                $join->on('direct_card.business_id', '=', 'card_pop.business_id')
                    ->on('direct_card.pump_payment_id', '=', 'card_pop.id');
            })
            ->leftJoinSub($legacy, 'legacy_card', function ($join) {
                $join->on('legacy_card.business_id', '=', 'card_pop.business_id')
                    ->on('legacy_card.pump_operator_id', '=', 'card_pop.pump_operator_id')
                    ->on('legacy_card.shift_id', '=', 'card_pop.shift_id')
                    ->whereRaw('CAST(legacy_card.collection_no AS CHAR) = CAST(card_pop.collection_form_no AS CHAR)');
            })
            ->leftJoinSub($dashboardFallback, 'dashboard_card', function ($join) {
                $join->on('dashboard_card.business_id', '=', 'card_pop.business_id')
                    ->on('dashboard_card.pump_operator_id', '=', 'card_pop.pump_operator_id')
                    ->on('dashboard_card.amount', '=', 'card_pop.payment_amount')
                    ->whereRaw('CAST(dashboard_card.collection_no AS CHAR) = CAST(card_pop.collection_form_no AS CHAR)');
            })
            ->where('card_pop.business_id', $businessId)
            ->whereIn(DB::raw('LOWER(TRIM(card_pop.payment_type))'), ['card', 'cards'])
            ->select([
                'card_pop.id as pump_payment_id',
                DB::raw('COALESCE(direct_card.id, legacy_card.id, dashboard_card.id) as id'),
                DB::raw('COALESCE(direct_card.customer_id, legacy_card.customer_id, dashboard_card.customer_id) as customer_id'),
                DB::raw("COALESCE(NULLIF(TRIM(direct_card.slip_no), ''), NULLIF(TRIM(legacy_card.slip_no), ''), NULLIF(TRIM(dashboard_card.slip_no), '')) as slip_no"),
                DB::raw('COALESCE(direct_card.amount, legacy_card.amount, dashboard_card.amount) as amount'),
                DB::raw('CASE '
                    . 'WHEN direct_card.id IS NOT NULL THEN direct_card.match_count '
                    . 'WHEN legacy_card.id IS NOT NULL THEN legacy_card.match_count '
                    . 'ELSE COALESCE(dashboard_card.match_count, 0) END as match_count'),
            ]);
    }

    private function chequeMetadataSubquery(int $businessId): Builder
    {
        $directColumn = Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')
            ? 'pump_payment_id'
            : (Schema::hasColumn('daily_cheque_payments', 'linked_payment_id') ? 'linked_payment_id' : null);

        $query = DB::table('daily_cheque_payments')
            ->where('business_id', $businessId);

        if ($directColumn === null) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereNotNull($directColumn)->where($directColumn, '>', 0);
        }

        return $query->select([
            'business_id',
            DB::raw(($directColumn ?? 'NULL') . ' as pump_payment_id'),
            DB::raw('CASE WHEN COUNT(*) = 1 THEN MIN(id) ELSE NULL END as id'),
            DB::raw('CASE WHEN COUNT(*) = 1 THEN MIN(customer_id) ELSE NULL END as customer_id'),
            DB::raw('COUNT(*) as match_count'),
        ])->groupBy('business_id', DB::raw($directColumn ?? 'NULL'));
    }

    private function settlementMetadataSubquery(int $businessId): Builder
    {
        $byId = DB::table('settlements')
            ->where('business_id', $businessId)
            ->select([
                'business_id',
                DB::raw('CAST(id AS CHAR) as settlement_ref'),
                'id',
                'settlement_no',
                'status',
            ]);

        $byNumber = DB::table('settlements')
            ->where('business_id', $businessId)
            ->whereNotNull('settlement_no')
            ->where('settlement_no', '<>', '')
            ->select([
                'business_id',
                DB::raw('CAST(settlement_no AS CHAR) as settlement_ref'),
                'id',
                'settlement_no',
                'status',
            ]);

        $refs = $byId->unionAll($byNumber);

        return DB::query()->fromSub($refs, 'settlement_refs')
            ->select([
                'business_id',
                'settlement_ref',
                DB::raw('MAX(id) as id'),
                DB::raw('MAX(settlement_no) as settlement_no'),
                DB::raw('MAX(status) as status'),
            ])
            ->groupBy('business_id', 'settlement_ref');
    }

    /**
     * SQL expression used by every list/footer/report path for the financial
     * amount recognized by settlement. Credit uses net; legacy rows fall back
     * to payment_amount without changing the stored historical record.
     */
    public function authoritativeAmountSql(string $table = 'pump_operator_payments'): string
    {
        $prefix = $table !== '' ? rtrim($table, '.') . '.' : '';
        $hasNet = Schema::hasColumn('pump_operator_payments', 'net_amount');
        $hasGross = Schema::hasColumn('pump_operator_payments', 'gross_amount');
        $hasDiscount = Schema::hasColumn('pump_operator_payments', 'discount_amount');

        $type = $prefix . 'payment_type';
        $payment = $prefix . 'payment_amount';
        $net = $prefix . 'net_amount';
        $gross = $prefix . 'gross_amount';
        $discount = $prefix . 'discount_amount';

        if ($hasNet && $hasGross && $hasDiscount) {
            return "CASE WHEN LOWER({$type}) IN ('credit','multiple_credit') "
                . "THEN COALESCE({$net}, COALESCE({$gross}, {$payment}) - COALESCE({$discount}, 0)) "
                . "ELSE COALESCE({$net}, {$payment}) END";
        }
        if ($hasNet) {
            return "COALESCE({$net}, {$payment})";
        }

        return $payment;
    }

    private function normalizeType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'cards' => 'card',
            'cheques' => 'cheque',
            'multiple_credit' => 'credit',
            default => strtolower(trim($type)),
        };
    }
}
