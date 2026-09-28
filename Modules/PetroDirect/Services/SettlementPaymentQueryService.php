<?php

namespace Modules\PetroDirect\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read facade over pump_operator_payments — single canonical query surface
 * for "give me the payment lines for shift X / settlement Y / pumper Z".
 *
 * All public methods return rows in the binding shape documented in
 * docs/PETRO_REFACTOR_DAY1.md Step 5. Consumers must rely ONLY on these
 * column names — internal column renames stay invisible.
 *
 * Today this service is used optionally — it provides a clean alternative
 * to the scattered DB::table('pump_operator_payments')->... queries across
 * 12+ controllers. Week 1 migrates the controllers' read paths to call
 * this service exclusively.
 */
class SettlementPaymentQueryService
{
    /**
     * Binding return shape — every method returns Collection of arrays
     * with exactly these keys.
     */
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
        // settlement_no on pump_operator_payments is a varchar; numeric settlement.id
        // is stored as its string form (legacy convention). Match both representations.
        return $this->base()
            ->where('pop.business_id', $businessId)
            ->where(function ($q) use ($settlementId) {
                $q->where('pop.settlement_no', (string) $settlementId)
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
        $q = $this->base()
            ->where('pop.business_id', $businessId)
            ->where('pop.pump_operator_id', $operatorId);
        if ($shiftId !== null) {
            $q->where('pop.shift_id', $shiftId);
        }
        return $q->orderBy('pop.id')
            ->get()
            ->map([$this, 'shape'])
            ->values();
    }

    /**
     * Look up one payment line by its pump_operator_payments.id (the source
     * of pump_payment_id on settlement_*_payments rows).
     *
     * @return array<string,mixed>|null
     */
    public function paymentByPumpPaymentId(int $pumpPaymentId): ?array
    {
        $row = $this->base()->where('pop.id', $pumpPaymentId)->first();
        return $row ? $this->shape($row) : null;
    }

    public function paymentSummaryBaseQuery(int $businessId, bool $hasCardMetaColumns = false, bool $includeCheque = false)
    {
        $query = DB::table('pump_operator_payments')
            ->leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('users as edited_user', 'pump_operator_payments.edited_by', '=', 'edited_user.id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'pump_operators.location_id')
            ->leftJoin('pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_payments.shift_id')
                    ->on('pump_operator_assignments.pump_operator_id', '=', 'pump_operator_payments.pump_operator_id');
            })
            ->leftJoin('settlement_credit_sale_payments as scsp', function ($join) {
                $join->on('scsp.pump_payment_id', '=', 'pump_operator_payments.id');
            })
            ->leftJoin('daily_vouchers as dv', 'dv.id', '=', 'scsp.daily_voucher_id')
            ->leftJoin('contacts as contacts_credit', 'contacts_credit.id', '=', 'scsp.customer_id')
            ->leftJoin('daily_cards as dc', function ($join) {
                $join->on('dc.business_id', '=', 'pump_operator_payments.business_id')
                    ->on(DB::raw('dc.amount'), '=', DB::raw('pump_operator_payments.payment_amount'))
                    ->whereRaw('dc.collection_no COLLATE utf8mb4_unicode_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
            });

        if ($hasCardMetaColumns) {
            $query->leftJoin('contacts as contacts_card', 'contacts_card.id', '=', 'pump_operator_payments.customer_id')
                ->leftJoin('contacts as contacts_card_legacy', 'contacts_card_legacy.id', '=', 'dc.customer_id');
        } else {
            $query->leftJoin('contacts as contacts_card', 'contacts_card.id', '=', 'dc.customer_id');
        }

        if ($includeCheque) {
            $query->leftJoin('daily_cheque_payments as dcp', function ($join) {
                $join->on('dcp.business_id', '=', 'pump_operator_payments.business_id')
                    ->on('dcp.linked_payment_id', '=', 'pump_operator_payments.id')
                    ->where('pump_operator_payments.payment_type', 'cheque');
            })
            ->leftJoin('contacts as contacts_cheque', 'contacts_cheque.id', '=', 'dcp.customer_id');
        }

        return $query->where('pump_operator_payments.business_id', $businessId);
    }

    /**
     * Shared base query — keeps the select shape consistent across all
     * methods so the post-shape map always finds the same columns.
     */
    private function base()
    {
        return DB::table('pump_operator_payments as pop')
            ->select(
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
                'pop.updated_at'
            );
    }

    /**
     * Coerce the DB row into the binding shape. settlement_id is computed
     * from settlement_no when numeric (legacy convention stores numeric ID
     * as string); NULL otherwise.
     */
    public function shape($row): array
    {
        $settlementNo = $row->settlement_no ?? null;
        $settlementId = (is_string($settlementNo) && ctype_digit($settlementNo))
            ? (int) $settlementNo
            : null;

        return [
            'pump_payment_id'    => (int) $row->pump_payment_id,
            'business_id'        => (int) $row->business_id,
            'pump_operator_id'   => $row->pump_operator_id !== null ? (int) $row->pump_operator_id : null,
            'shift_id'           => $row->shift_id !== null ? (int) $row->shift_id : null,
            'settlement_id'      => $settlementId,
            'settlement_no'      => $settlementNo,
            'collection_form_no' => $row->collection_form_no ?? null,
            'payment_type'       => $row->payment_type,
            'payment_amount'     => (string) $row->payment_amount,
            'is_used'            => (int) ($row->is_used ?? 0),
            'parent_id'          => $row->parent_id !== null ? (int) $row->parent_id : null,
            'created_at'         => $row->created_at !== null ? (string) $row->created_at : null,
            'updated_at'         => $row->updated_at !== null ? (string) $row->updated_at : null,
        ];
    }
}
