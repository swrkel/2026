<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * The authoritative read/reconciliation surface for Pumper Dashboard payments.
 *
 * Financial totals are always built from one unique pump_operator_payments.id.
 * Supporting tables are used only for metadata and legacy discount/net backfill.
 */
class PumpOperatorPaymentAuthorityService
{
    private const TOLERANCE = 0.02;

    /**
     * @return array{
     *   payments: Collection,
     *   payment_ids: array<int>,
     *   gross_total: float,
     *   discount_total: float,
     *   net_total: float,
     *   detail_net_total: float,
     *   issues: array<int,array<string,mixed>>
     * }
     */
    public function creditSummary(int $businessId, int $operatorId, array $shiftIds): array
    {
        $shiftIds = $this->normalizeShiftIds($shiftIds);

        if (empty($shiftIds)) {
            return $this->emptySummary([[
                'type' => 'missing_shift_scope',
                'message' => 'No Shift ID was supplied for the authoritative credit-payment query.',
            ]]);
        }

        $select = [
            'id',
            'business_id',
            'pump_operator_id',
            'shift_id',
            'collection_form_no',
            'payment_type',
            'payment_amount',
        ];

        foreach (['gross_amount', 'discount_amount', 'net_amount', 'source_type', 'source_id'] as $column) {
            if (Schema::hasColumn('pump_operator_payments', $column)) {
                $select[] = $column;
            }
        }

        $payments = DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn('shift_id', $shiftIds)
            ->whereIn(DB::raw('LOWER(payment_type)'), ['credit', 'multiple_credit'])
            ->select($select)
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();

        if ($payments->isEmpty()) {
            return $this->emptySummary();
        }

        $paymentIds = $payments->pluck('id')->map(fn ($id) => (int) $id)->all();
        $detailsByPayment = $this->creditDetailsByPumpPayment($businessId, $paymentIds);
        $legacyDetailsByCollection = $this->legacyCreditDetailsByCollection(
            $businessId,
            $operatorId,
            $payments,
            $shiftIds
        );

        $issues = $this->creditShiftIntegrityIssues($businessId, $operatorId, $shiftIds);
        $rows = $payments->map(function ($payment) use ($detailsByPayment, $legacyDetailsByCollection, $shiftIds, &$issues) {
            $paymentId = (int) $payment->id;
            $detail = $detailsByPayment->get($paymentId);

            if (! $detail && ! empty($payment->collection_form_no)) {
                $legacyKey = $this->legacyCreditKey(
                    (string) $payment->collection_form_no,
                    (int) $payment->shift_id,
                    Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                );
                $legacy = $legacyDetailsByCollection->get($legacyKey);
                if ($legacy && (int) ($legacy->matching_payment_count ?? 0) === 1) {
                    $detail = $legacy;
                } elseif ($legacy && (int) ($legacy->matching_payment_count ?? 0) > 1) {
                    $issues[] = [
                        'type' => 'ambiguous_legacy_credit_link',
                        'pump_payment_id' => $paymentId,
                        'collection_form_no' => $payment->collection_form_no,
                        'message' => 'More than one master payment uses the same legacy collection number.',
                    ];
                }
            }

            $detailIsConsistent = $detail && ! empty($detail->financially_consistent);

            $gross = $this->nullableFloat($payment->gross_amount ?? null);
            if ($gross === null) {
                // payment_amount remains the legacy-compatible gross credit amount.
                $gross = (float) ($payment->payment_amount ?? 0);
            }

            $discount = $this->nullableFloat($payment->discount_amount ?? null);
            if ($discount === null) {
                $discount = $detailIsConsistent
                    ? (float) ($detail->discount_total ?? 0)
                    : 0.0;
            }

            $net = $this->nullableFloat($payment->net_amount ?? null);
            if ($net === null) {
                $detailNet = $detailIsConsistent
                    ? $this->nullableFloat($detail->net_total ?? null)
                    : null;
                $net = $detailNet !== null ? $detailNet : ($gross - $discount);
            }

            $net = round($net, 4);
            $gross = round($gross, 4);
            $discount = round($discount, 4);

            if (! in_array((int) $payment->shift_id, $shiftIds, true)) {
                $issues[] = [
                    'type' => 'master_shift_outside_settlement',
                    'pump_payment_id' => $paymentId,
                    'payment_shift_id' => (int) $payment->shift_id,
                    'message' => 'The master payment Shift ID is outside the settlement shift scope.',
                ];
            }

            if ($detail && isset($detail->min_shift_id, $detail->max_shift_id)) {
                $minShift = (int) $detail->min_shift_id;
                $maxShift = (int) $detail->max_shift_id;
                if (($minShift > 0 && $minShift !== (int) $payment->shift_id)
                    || ($maxShift > 0 && $maxShift !== (int) $payment->shift_id)) {
                    $issues[] = [
                        'type' => 'credit_detail_shift_mismatch',
                        'pump_payment_id' => $paymentId,
                        'master_shift_id' => (int) $payment->shift_id,
                        'detail_min_shift_id' => $minShift,
                        'detail_max_shift_id' => $maxShift,
                        'message' => 'Credit-sale detail Shift ID does not match its master payment Shift ID.',
                    ];
                }
            }

            if (! $detail) {
                $issues[] = [
                    'type' => 'missing_credit_detail',
                    'pump_payment_id' => $paymentId,
                    'collection_form_no' => $payment->collection_form_no,
                    'message' => 'The master credit payment has no linked credit-sale detail record.',
                ];
            } elseif ($detailIsConsistent) {
                $detailNet = (float) ($detail->net_total ?? 0);
                if (abs($detailNet - $net) >= self::TOLERANCE) {
                    $issues[] = [
                        'type' => 'credit_net_mismatch',
                        'pump_payment_id' => $paymentId,
                        'master_net_amount' => $net,
                        'detail_net_amount' => round($detailNet, 4),
                        'message' => 'Master credit net amount and supporting credit-sale net amount do not agree.',
                    ];
                }
            }

            return (object) [
                'pump_payment_id' => $paymentId,
                'business_id' => (int) $payment->business_id,
                'pump_operator_id' => (int) $payment->pump_operator_id,
                'shift_id' => (int) $payment->shift_id,
                'collection_form_no' => $payment->collection_form_no,
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'net_amount' => $net,
                'detail_net_amount' => $detailIsConsistent
                    ? (float) ($detail->net_total ?? 0)
                    : 0.0,
                'detail_count' => (int) ($detail->detail_count ?? 0),
            ];
        })->values();

        return [
            'payments' => $rows,
            'payment_ids' => $paymentIds,
            'gross_total' => round((float) $rows->sum('gross_amount'), 4),
            'discount_total' => round((float) $rows->sum('discount_amount'), 4),
            'net_total' => round((float) $rows->sum('net_amount'), 4),
            'detail_net_total' => round((float) $rows->sum('detail_net_amount'), 4),
            'issues' => $issues,
        ];
    }

    /**
     * Reject settlement finalization when the immutable payment/shift chain is broken.
     */
    public function assertCreditSettlementCanFinalize(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        ?int $settlementId = null,
        ?string $settlementNo = null
    ): array {
        $summary = $this->creditSummary($businessId, $operatorId, $shiftIds);

        if (! empty($summary['issues'])) {
            Log::error('PETROPD authoritative credit-payment reconciliation failed', [
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => $this->normalizeShiftIds($shiftIds),
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'gross_total' => $summary['gross_total'],
                'discount_total' => $summary['discount_total'],
                'net_total' => $summary['net_total'],
                'detail_net_total' => $summary['detail_net_total'],
                'issues' => $summary['issues'],
            ]);

            throw new RuntimeException(
                'Credit-sale reconciliation failed for this shift. The settlement was not finalized. '
                . 'Please review the logged Pump Payment IDs and correct their Shift ID/payment links.'
            );
        }

        return $summary;
    }

    private function creditDetailsByPumpPayment(int $businessId, array $paymentIds): Collection
    {
        if (empty($paymentIds)
            || ! Schema::hasTable('settlement_credit_sale_payments')
            || ! Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            return collect();
        }

        $hasShiftId = Schema::hasColumn('settlement_credit_sale_payments', 'shift_id');
        $rowNetExpression = Schema::hasColumn('settlement_credit_sale_payments', 'sub_total')
            ? 'COALESCE(sub_total, amount - COALESCE(total_discount, 0))'
            : 'COALESCE(amount, 0) - COALESCE(total_discount, 0)';

        $query = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $businessId)
            ->whereIn('pump_payment_id', $paymentIds)
            ->whereNotNull('pump_payment_id')
            ->groupBy('pump_payment_id')
            ->select(
                'pump_payment_id',
                DB::raw('SUM(COALESCE(amount, 0)) as min_gross'),
                DB::raw('SUM(COALESCE(amount, 0)) as max_gross'),
                DB::raw('SUM(COALESCE(total_discount, 0)) as min_discount'),
                DB::raw('SUM(COALESCE(total_discount, 0)) as max_discount'),
                DB::raw('SUM(' . $rowNetExpression . ') as min_net'),
                DB::raw('SUM(' . $rowNetExpression . ') as max_net'),
                DB::raw('COUNT(*) as detail_count')
            );

        if ($hasShiftId) {
            $query->addSelect(
                DB::raw('MIN(COALESCE(shift_id, 0)) as min_shift_id'),
                DB::raw('MAX(COALESCE(shift_id, 0)) as max_shift_id')
            );
        } else {
            $query->addSelect(
                DB::raw('0 as min_shift_id'),
                DB::raw('0 as max_shift_id')
            );
        }

        return $query->get()
            ->each(fn ($row) => $this->setCanonicalDetailAmounts($row))
            ->keyBy(fn ($row) => (int) $row->pump_payment_id);
    }

    private function legacyCreditDetailsByCollection(
        int $businessId,
        int $operatorId,
        Collection $payments,
        array $shiftIds
    ): Collection {
        if (! Schema::hasTable('settlement_credit_sale_payments')) {
            return collect();
        }

        $collectionNumbers = $payments->pluck('collection_form_no')
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => (string) $value)
            ->unique()
            ->values();

        if ($collectionNumbers->isEmpty()) {
            return collect();
        }

        $hasShiftId = Schema::hasColumn('settlement_credit_sale_payments', 'shift_id');
        $rowNetExpression = Schema::hasColumn('settlement_credit_sale_payments', 'sub_total')
            ? 'COALESCE(sub_total, amount - COALESCE(total_discount, 0))'
            : 'COALESCE(amount, 0) - COALESCE(total_discount, 0)';

        // Count master candidates from the database, not only from the current
        // collection. Without a detail Shift ID, a reused collection number is
        // unsafe and must never be guessed.
        $masterCountQuery = DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn(DB::raw('LOWER(payment_type)'), ['credit', 'multiple_credit'])
            ->whereIn('collection_form_no', $collectionNumbers->all());

        if ($hasShiftId) {
            $masterCounts = $masterCountQuery
                ->whereIn('shift_id', $shiftIds)
                ->groupBy('collection_form_no', 'shift_id')
                ->select('collection_form_no', 'shift_id', DB::raw('COUNT(*) as payment_count'))
                ->get()
                ->keyBy(fn ($row) => $this->legacyCreditKey(
                    (string) $row->collection_form_no,
                    (int) $row->shift_id,
                    true
                ));
        } else {
            $masterCounts = $masterCountQuery
                ->groupBy('collection_form_no')
                ->select('collection_form_no', DB::raw('COUNT(*) as payment_count'))
                ->get()
                ->keyBy(fn ($row) => $this->legacyCreditKey(
                    (string) $row->collection_form_no,
                    0,
                    false
                ));
        }

        $query = DB::table('settlement_credit_sale_payments')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn('collection_form_no', $collectionNumbers->all());

        if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            $query->whereNull('pump_payment_id');
        }

        if ($hasShiftId) {
            $query->whereIn('shift_id', $shiftIds)
                ->groupBy('collection_form_no', 'shift_id')
                ->select(
                    'collection_form_no',
                    'shift_id',
                    DB::raw('SUM(COALESCE(amount, 0)) as min_gross'),
                    DB::raw('SUM(COALESCE(amount, 0)) as max_gross'),
                    DB::raw('SUM(COALESCE(total_discount, 0)) as min_discount'),
                    DB::raw('SUM(COALESCE(total_discount, 0)) as max_discount'),
                    DB::raw('SUM(' . $rowNetExpression . ') as min_net'),
                    DB::raw('SUM(' . $rowNetExpression . ') as max_net'),
                    DB::raw('COUNT(*) as detail_count'),
                    DB::raw('MIN(COALESCE(shift_id, 0)) as min_shift_id'),
                    DB::raw('MAX(COALESCE(shift_id, 0)) as max_shift_id')
                );
        } else {
            $query->groupBy('collection_form_no')
                ->select(
                    'collection_form_no',
                    DB::raw('SUM(COALESCE(amount, 0)) as min_gross'),
                    DB::raw('SUM(COALESCE(amount, 0)) as max_gross'),
                    DB::raw('SUM(COALESCE(total_discount, 0)) as min_discount'),
                    DB::raw('SUM(COALESCE(total_discount, 0)) as max_discount'),
                    DB::raw('SUM(' . $rowNetExpression . ') as min_net'),
                    DB::raw('SUM(' . $rowNetExpression . ') as max_net'),
                    DB::raw('COUNT(*) as detail_count'),
                    DB::raw('0 as min_shift_id'),
                    DB::raw('0 as max_shift_id')
                );
        }

        return $query->get()
            ->each(function ($row) use ($masterCounts, $hasShiftId) {
                $this->setCanonicalDetailAmounts($row);

                $key = $this->legacyCreditKey(
                    (string) $row->collection_form_no,
                    (int) ($row->shift_id ?? 0),
                    $hasShiftId
                );
                $row->matching_payment_count = (int) ($masterCounts->get($key)->payment_count ?? 0);
            })
            ->keyBy(fn ($row) => $this->legacyCreditKey(
                (string) $row->collection_form_no,
                (int) ($row->shift_id ?? 0),
                $hasShiftId
            ));
    }

    /**
     * Detect orphan or cross-shift credit details inside the settlement scope.
     * These records must block finalization rather than becoming Excess.
     */
    private function creditShiftIntegrityIssues(int $businessId, int $operatorId, array $shiftIds): array
    {
        if (! Schema::hasTable('settlement_credit_sale_payments')
            || ! Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
            return [];
        }

        $hasPumpPaymentId = Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id');
        if (! $hasPumpPaymentId) {
            return [[
                'type' => 'missing_pump_payment_link_column',
                'message' => 'Credit-sale details cannot be reconciled because pump_payment_id is unavailable.',
            ]];
        }

        $rows = DB::table('settlement_credit_sale_payments as scsp')
            ->leftJoin('pump_operator_payments as pop', 'pop.id', '=', 'scsp.pump_payment_id')
            ->where('scsp.business_id', $businessId)
            ->where('scsp.pump_operator_id', $operatorId)
            ->whereIn('scsp.shift_id', $shiftIds)
            ->where(function ($query) {
                $query->whereNull('scsp.pump_payment_id')
                    ->orWhereNull('pop.id')
                    ->orWhereColumn('pop.business_id', '<>', 'scsp.business_id')
                    ->orWhereColumn('pop.pump_operator_id', '<>', 'scsp.pump_operator_id')
                    ->orWhereColumn('pop.shift_id', '<>', 'scsp.shift_id');
            })
            ->get([
                'scsp.id as credit_detail_id',
                'scsp.pump_payment_id',
                'scsp.shift_id as detail_shift_id',
                'pop.shift_id as master_shift_id',
            ]);

        return $rows->map(function ($row) {
            return [
                'type' => empty($row->pump_payment_id)
                    ? 'orphan_credit_detail'
                    : 'credit_detail_master_scope_mismatch',
                'credit_detail_id' => (int) $row->credit_detail_id,
                'pump_payment_id' => $row->pump_payment_id !== null ? (int) $row->pump_payment_id : null,
                'detail_shift_id' => (int) $row->detail_shift_id,
                'master_shift_id' => $row->master_shift_id !== null ? (int) $row->master_shift_id : null,
                'message' => 'Credit-sale detail is not linked to one master payment in the same immutable Shift ID.',
            ];
        })->all();
    }

    private function legacyCreditKey(string $collectionFormNo, int $shiftId, bool $includeShift): string
    {
        return $includeShift
            ? $collectionFormNo . '|' . $shiftId
            : $collectionFormNo;
    }

    private function setCanonicalDetailAmounts(object $row): void
    {
        // A grouped credit payment may legitimately contain several bill rows.
        // The query aliases MIN/MAX to the same SUM totals, so exact duplicate
        // rows still fail later when the aggregate no longer matches the master.
        $consistent = abs((float) $row->max_gross - (float) $row->min_gross) < self::TOLERANCE
            && abs((float) $row->max_discount - (float) $row->min_discount) < self::TOLERANCE
            && abs((float) $row->max_net - (float) $row->min_net) < self::TOLERANCE;

        $row->financially_consistent = $consistent;
        $row->gross_total = $consistent ? (float) $row->max_gross : null;
        $row->discount_total = $consistent ? (float) $row->max_discount : null;
        $row->net_total = $consistent ? (float) $row->max_net : null;
    }

    private function normalizeShiftIds(array $shiftIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $shiftIds), fn ($id) => $id > 0)));
    }

    private function nullableFloat($value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function emptySummary(array $issues = []): array
    {
        return [
            'payments' => collect(),
            'payment_ids' => [],
            'gross_total' => 0.0,
            'discount_total' => 0.0,
            'net_total' => 0.0,
            'detail_net_total' => 0.0,
            'issues' => $issues,
        ];
    }
}
