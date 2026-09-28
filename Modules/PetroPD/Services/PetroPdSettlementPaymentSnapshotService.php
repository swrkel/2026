<?php

namespace Modules\PetroPD\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Builds one immutable financial snapshot for a PetroPD settlement.
 *
 * Rules:
 * - pump_operator_payments.id is the only Pumper Dashboard payment identity.
 * - Every master payment is counted once, regardless of how many supporting rows exist.
 * - Supporting tables provide metadata only and are reconciled against the master.
 * - business_id + pump_operator_id + shift_id never come from a "latest shift" fallback.
 */
class PetroPdSettlementPaymentSnapshotService
{
    private const TOLERANCE = 0.02;

    private const TYPE_ALIASES = [
        'cards' => 'card',
        'cheques' => 'cheque',
        'multiple_credit' => 'credit',
    ];

    private const RECOGNIZED_TYPES = [
        'cash',
        'card',
        'cheque',
        'credit',
        'other',
        'shortage',
        'excess',
    ];

    /**
     * Existing tenant databases contain historical supporting rows created before
     * pump_payment_id/shift_id became mandatory. Those rows are useful metadata,
     * but pump_operator_payments is the financial authority. A supporting-row
     * mismatch must therefore be audited without preventing a balanced settlement
     * from being finalized. Only authoritative master/scope failures remain hard
     * blockers.
     */
    private const NON_BLOCKING_SUPPORT_ISSUES = [
        'ambiguous_legacy_credit_link',
        'credit_detail_shift_mismatch',
        'missing_credit_detail',
        'credit_net_mismatch',
        'missing_pump_payment_link_column',
        'orphan_credit_detail',
        'credit_detail_master_scope_mismatch',
        'missing_detail_authority_columns',
        'missing_payment_detail_link',
        'multiple_payment_details_for_master',
        'payment_detail_scope_mismatch',
        'payment_detail_operator_mismatch',
        'payment_detail_amount_mismatch',
        'missing_operational_authority_columns',
        'missing_operational_payment_link',
        'multiple_operational_rows_for_master',
        'operational_payment_scope_mismatch',
        'operational_payment_operator_mismatch',
        'operational_payment_amount_mismatch',
        'duplicate_master_source_identity',
    ];

    /**
     * Settlement detail table used only to reconcile the master payment.
     * "other" deliberately has no detail table: its master row is sufficient.
     */
    private const DETAIL_TABLES = [
        'cash' => 'settlement_cash_payments',
        'card' => 'settlement_card_payments',
        'cheque' => 'settlement_cheque_payments',
        'credit' => 'settlement_credit_sale_payments',
        'shortage' => 'settlement_shortage_payments',
        'excess' => 'settlement_excess_payments',
    ];

    private const OPERATIONAL_TABLES = [
        'cash' => [
            'table' => 'daily_collections',
            'amount_column' => 'current_amount',
            'operator_column' => 'pump_operator_id',
        ],
        'card' => [
            'table' => 'daily_cards',
            'amount_column' => 'amount',
            'operator_column' => 'pump_operator_id',
        ],
        'cheque' => [
            'table' => 'daily_cheque_payments',
            'amount_column' => 'amount',
            'operator_column' => 'pump_operator_id',
        ],
        'credit' => [
            'table' => 'daily_vouchers',
            'amount_column' => 'total_amount',
            'operator_column' => 'operator_id',
            // daily_vouchers.shift_id belongs to the legacy
            // petro_daily_shifts table. PetroPD shift authority is held by
            // pump_operator_payments / settlement_credit_sale_payments.
            'requires_shift' => false,
            // The Daily Voucher is an operational/accounting mirror. Once the
            // authoritative master and settlement credit bill rows reconcile,
            // a missing legacy voucher link must not block finalization.
            'required_for_finalization' => false,
        ],
    ];

    public function __construct(
        private readonly PumpOperatorPaymentAuthorityService $creditAuthority
    ) {
    }

    /**
     * Repair the legacy PetroPD draft-link mismatch without weakening the
     * finalized-settlement protection.
     *
     * Older PetroPD/Pumper Dashboard code could create a temporary normal Petro
     * settlement (for example ST...) before the PD Settlement screen created its
     * proper PD settlement (for example PDST...). The Pump Operator Payments were
     * then left linked to the temporary active draft, causing finalization to stop
     * with "already linked to a different settlement".
     *
     * A link is moved only when every safety condition is true:
     * - the current settlement is an active draft for this business/operator;
     * - the previous owner is also an active draft, never a finalized settlement;
     * - both drafts belong to exactly the same immutable Shift ID;
     * - the previous owner has no payment scope outside that same Shift ID.
     *
     * This also covers duplicate active PD drafts created by older page refreshes.
     * Finalized settlements and multi-shift owners are never changed.
     *
     * @return array{updated_payment_ids: array<int>, skipped_payment_ids: array<int>}
     */
    public function normalizeLegacyDraftSettlementLinks(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        int $settlementId,
        string $settlementNo
    ): array {
        $shiftIds = $this->normalizeShiftIds($shiftIds);
        $result = [
            'updated_payment_ids' => [],
            'skipped_payment_ids' => [],
        ];

        if (count($shiftIds) !== 1
            || ! Schema::hasTable('settlements')
            || ! Schema::hasTable('pump_operator_payments')) {
            return $result;
        }

        $currentSettlement = DB::table('settlements')
            ->where('id', $settlementId)
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->first();

        if (! $currentSettlement
            || ! $this->isActiveDraftSettlement($currentSettlement)
            || trim((string) ($currentSettlement->settlement_no ?? '')) !== trim($settlementNo)) {
            return $result;
        }

        $currentKeys = array_values(array_unique([
            (string) $settlementId,
            trim($settlementNo),
        ]));

        return DB::transaction(function () use (
            $businessId,
            $operatorId,
            $shiftIds,
            $settlementId,
            $settlementNo,
            $currentKeys,
            $result
        ) {
            $payments = DB::table('pump_operator_payments')
                ->where('business_id', $businessId)
                ->where('pump_operator_id', $operatorId)
                ->where('shift_id', $shiftIds[0])
                ->whereNotNull('settlement_no')
                ->where('settlement_no', '<>', '')
                ->select(['id', 'settlement_no', 'created_at'])
                ->lockForUpdate()
                ->get();

            foreach ($payments as $payment) {
                $storedSettlement = trim((string) ($payment->settlement_no ?? ''));
                if ($storedSettlement === '' || in_array($storedSettlement, $currentKeys, true)) {
                    continue;
                }

                // Pump Operator Payments historically stored either the
                // numeric settlement ID or the display settlement number. Prefer
                // the numeric primary key when the stored value is all digits so
                // an unusual numeric display number can never select the wrong row.
                $owner = null;
                if (ctype_digit($storedSettlement)) {
                    $owner = DB::table('settlements')
                        ->where('business_id', $businessId)
                        ->where('id', (int) $storedSettlement)
                        ->lockForUpdate()
                        ->first();
                }

                if (! $owner) {
                    $owner = DB::table('settlements')
                        ->where('business_id', $businessId)
                        ->where('settlement_no', $storedSettlement)
                        ->lockForUpdate()
                        ->first();
                }

                $repairReason = null;

                // A missing owner is an orphan reference. The payment itself is
                // already locked and belongs to the exact business/operator/Shift
                // being finalized, so attaching it to the current draft is safe.
                if (! $owner) {
                    $repairReason = 'orphan_settlement_reference';
                } elseif ((int) ($owner->id ?? 0) === $settlementId) {
                    $result['skipped_payment_ids'][] = (int) $payment->id;
                    continue;
                } elseif ((int) ($owner->pump_operator_id ?? 0) !== $operatorId) {
                    // The master payment is already constrained to this exact
                    // business/operator/Shift. A settlement owned by another
                    // operator (or no operator) cannot legitimately own it.
                    // This is the legacy ST/PDST cross-link shown by IS1831.
                    $repairReason = 'owner_operator_scope_mismatch';
                } elseif ($this->isActiveDraftSettlement($owner)) {
                    // Older Pumper Dashboard code reused one active ST/PDST draft
                    // for several shifts. Split only this exact Shift's payment
                    // into the draft currently being finalized; payments for all
                    // other shifts remain untouched on the old draft.
                    $repairReason = 'active_draft_shift_split';
                } elseif ($this->isShiftAssignedExclusivelyToCurrentSettlement(
                    $businessId,
                    $operatorId,
                    $shiftIds[0],
                    $settlementId
                )) {
                    // The assignment table is the authoritative owner of a
                    // closed Shift. If this exact Shift is attached to the
                    // current draft and has no attachment to the older owner,
                    // the payment inherited a stale operator settlement pointer.
                    $repairReason = 'current_shift_assignment_owner';
                } elseif ($this->isProvablyStaleFinalizedReference(
                    $owner,
                    $payment,
                    $businessId,
                    $operatorId,
                    $shiftIds[0],
                    $settlementId
                )) {
                    // A stale pump_operators.settlement_no could link a newly
                    // created payment to an older settlement after that settlement
                    // had already been finalized. Repair only when timestamps and
                    // intrinsic Shift ownership prove that the old finalized row
                    // could not legitimately own this payment.
                    $repairReason = 'stale_finalized_pointer';
                } else {
                    $result['skipped_payment_ids'][] = (int) $payment->id;
                    continue;
                }

                $update = ['settlement_no' => (string) $settlementId];
                if (Schema::hasColumn('pump_operator_payments', 'updated_at')) {
                    $update['updated_at'] = now();
                }

                $updated = DB::table('pump_operator_payments')
                    ->where('id', (int) $payment->id)
                    ->where('business_id', $businessId)
                    ->where('pump_operator_id', $operatorId)
                    ->where('shift_id', $shiftIds[0])
                    ->where('settlement_no', $storedSettlement)
                    ->update($update);

                if ($updated === 1) {
                    $paymentId = (int) $payment->id;
                    $result['updated_payment_ids'][] = $paymentId;

                    $this->relinkPaymentSupportingRows(
                        $paymentId,
                        $businessId,
                        $operatorId,
                        $shiftIds[0],
                        $settlementId,
                        $settlementNo,
                        $storedSettlement,
                        $owner
                    );

                    Log::notice('PETROPD repaired Pump Operator Payment settlement ownership', [
                        'repair_reason' => $repairReason,
                        'pump_payment_id' => $paymentId,
                        'old_settlement_reference' => $storedSettlement,
                        'new_settlement_id' => $settlementId,
                        'new_settlement_no' => $settlementNo,
                        'business_id' => $businessId,
                        'pump_operator_id' => $operatorId,
                        'shift_id' => $shiftIds[0],
                    ]);
                } else {
                    $result['skipped_payment_ids'][] = (int) $payment->id;
                }
            }

            if (! empty($result['updated_payment_ids'])) {
                if (Schema::hasTable('pump_operators')
                    && Schema::hasColumn('pump_operators', 'settlement_no')) {
                    $operatorQuery = DB::table('pump_operators')->where('id', $operatorId);
                    if (Schema::hasColumn('pump_operators', 'business_id')) {
                        $operatorQuery->where('business_id', $businessId);
                    }

                    $operatorUpdate = ['settlement_no' => $settlementNo];
                    if (Schema::hasColumn('pump_operators', 'updated_at')) {
                        $operatorUpdate['updated_at'] = now();
                    }
                    $operatorQuery->update($operatorUpdate);
                }

                Log::notice('PETROPD normalized safe settlement payment links', [
                    'business_id' => $businessId,
                    'pump_operator_id' => $operatorId,
                    'shift_ids' => $shiftIds,
                    'settlement_id' => $settlementId,
                    'settlement_no' => $settlementNo,
                    'updated_payment_ids' => $result['updated_payment_ids'],
                ]);
            }

            return $result;
        });
    }

    /**
     * Prove settlement ownership from the immutable Shift assignment.
     *
     * This is deliberately stricter than merely finding the Shift: at least one
     * assignment for the exact business/operator/Shift must point to the current
     * settlement, and none may point to the previous settlement.
     */
    private function isShiftAssignedExclusivelyToCurrentSettlement(
        int $businessId,
        int $operatorId,
        int $shiftId,
        int $currentSettlementId
    ): bool {
        if (! Schema::hasTable('pump_operator_assignments')
            || ! Schema::hasColumn('pump_operator_assignments', 'settlement_id')
            || ! Schema::hasColumn('pump_operator_assignments', 'shift_id')) {
            return false;
        }

        $assignmentQuery = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->where('shift_id', $shiftId);

        $belongsToCurrent = (clone $assignmentQuery)
            ->where('settlement_id', $currentSettlementId)
            ->exists();

        if (! $belongsToCurrent) {
            return false;
        }

        return ! (clone $assignmentQuery)
            ->whereNotNull('settlement_id')
            ->where('settlement_id', '<>', $currentSettlementId)
            ->exists();
    }

    /**
     * @return array{
     *   payments: Collection<int,object>,
     *   payment_ids: array<int>,
     *   totals: array<string,float>,
     *   credit_summary: array<string,mixed>,
     *   issues: array<int,array<string,mixed>>,
     *   blocking_issues: array<int,array<string,mixed>>,
     *   has_blocking_issues: bool,
     *   fingerprint: string
     * }
     */
    public function build(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        ?int $settlementId = null,
        ?string $settlementNo = null,
        bool $requireDetailLinks = false
    ): array {
        $shiftIds = $this->normalizeShiftIds($shiftIds);
        $issues = [];

        if (empty($shiftIds)) {
            $issues[] = $this->issue(
                'missing_shift_scope',
                'critical',
                'No valid Shift ID was supplied. PetroPD payment totals cannot be calculated safely.'
            );

            return $this->emptySnapshot($issues);
        }


        if (count($shiftIds) !== 1) {
            $issues[] = $this->issue(
                'multiple_shift_scope',
                'critical',
                'A PetroPD settlement payment snapshot must belong to exactly one immutable Shift ID.',
                ['shift_ids' => $shiftIds]
            );

            return $this->emptySnapshot($issues);
        }

        if (! Schema::hasTable('pump_operator_payments')) {
            $issues[] = $this->issue(
                'missing_master_payment_table',
                'critical',
                'The authoritative pump_operator_payments table is unavailable.'
            );

            return $this->emptySnapshot($issues);
        }

        $select = [
            'id',
            'business_id',
            'pump_operator_id',
            'shift_id',
            'settlement_no',
            'collection_form_no',
            'payment_type',
            'payment_amount',
            'is_used',
            'parent_id',
            'created_at',
            'updated_at',
        ];

        foreach ([
            'gross_amount',
            'discount_amount',
            'net_amount',
            'source_type',
            'source_id',
            'customer_id',
            'transaction_date',
            'reference_no',
        ] as $column) {
            if (Schema::hasColumn('pump_operator_payments', $column)) {
                $select[] = $column;
            }
        }

        $rawPayments = DB::table('pump_operator_payments')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn('shift_id', $shiftIds)
            ->select($select)
            ->orderBy('id')
            ->get();

        $duplicateIds = $rawPayments->groupBy('id')->filter(fn (Collection $rows) => $rows->count() > 1);
        foreach ($duplicateIds as $paymentId => $rows) {
            $issues[] = $this->issue(
                'duplicate_master_query_identity',
                'critical',
                'The authoritative query produced the same Pump Operator Payment more than once.',
                ['pump_payment_id' => (int) $paymentId, 'row_count' => $rows->count()]
            );
        }

        $rawPayments = $rawPayments->unique('id')->values();
        $creditSummary = $this->creditAuthority->creditSummary($businessId, $operatorId, $shiftIds);
        $creditById = collect($creditSummary['payments'] ?? [])->keyBy(
            fn ($row) => (int) ($row->pump_payment_id ?? 0)
        );

        foreach ($creditSummary['issues'] ?? [] as $creditIssue) {
            $creditIssue['severity'] = $creditIssue['severity'] ?? 'critical';
            $issues[] = $creditIssue;
        }

        $rows = $rawPayments->map(function ($payment) use (
            $creditById,
            $businessId,
            $operatorId,
            $shiftIds,
            $settlementId,
            $settlementNo,
            &$issues
        ) {
            $id = (int) $payment->id;
            $type = $this->normalizeType((string) $payment->payment_type);

            if (! in_array($type, self::RECOGNIZED_TYPES, true)) {
                $issues[] = $this->issue(
                    'unknown_payment_type',
                    'critical',
                    'An unsupported Pump Operator Payment type was found.',
                    ['pump_payment_id' => $id, 'payment_type' => $payment->payment_type]
                );
            }

            if ((int) $payment->business_id !== $businessId
                || (int) $payment->pump_operator_id !== $operatorId
                || ! in_array((int) $payment->shift_id, $shiftIds, true)) {
                $issues[] = $this->issue(
                    'master_scope_mismatch',
                    'critical',
                    'A Pump Operator Payment does not belong to the selected business, operator and Shift ID.',
                    [
                        'pump_payment_id' => $id,
                        'business_id' => (int) $payment->business_id,
                        'pump_operator_id' => (int) $payment->pump_operator_id,
                        'shift_id' => (int) $payment->shift_id,
                    ]
                );
            }

            $storedSettlement = trim((string) ($payment->settlement_no ?? ''));
            if ($storedSettlement !== '' && ! $this->matchesSettlement($storedSettlement, $settlementId, $settlementNo)) {
                $issues[] = $this->issue(
                    'master_linked_to_different_settlement',
                    'critical',
                    'A Pump Operator Payment is already linked to a different settlement.',
                    [
                        'pump_payment_id' => $id,
                        'payment_settlement_no' => $storedSettlement,
                        'current_settlement_id' => $settlementId,
                        'current_settlement_no' => $settlementNo,
                    ]
                );
            }

            $gross = $this->nullableFloat($payment->gross_amount ?? null);
            $discount = $this->nullableFloat($payment->discount_amount ?? null);
            $net = $this->nullableFloat($payment->net_amount ?? null);

            if ($type === 'credit') {
                $credit = $creditById->get($id);
                if ($credit) {
                    $gross = (float) $credit->gross_amount;
                    $discount = (float) $credit->discount_amount;
                    $net = (float) $credit->net_amount;
                }
            }

            $legacyAmount = (float) ($payment->payment_amount ?? 0);
            $gross ??= $legacyAmount;
            $discount ??= 0.0;
            $net ??= ($type === 'credit' ? $gross - $discount : $legacyAmount);

            $gross = round($gross, 4);
            $discount = round($discount, 4);
            $net = round($net, 4);

            if ($type === 'excess' && $net > self::TOLERANCE) {
                $issues[] = $this->issue(
                    'positive_excess_amount',
                    'critical',
                    'An Excess payment is positive. Excess must reduce Total Paid and cannot be silently converted.',
                    ['pump_payment_id' => $id, 'amount' => $net]
                );
            }

            if ($type === 'shortage' && $net < -self::TOLERANCE) {
                $issues[] = $this->issue(
                    'negative_shortage_amount',
                    'critical',
                    'A Shortage payment is negative and cannot be reconciled safely.',
                    ['pump_payment_id' => $id, 'amount' => $net]
                );
            }

            return (object) [
                'pump_payment_id' => $id,
                'business_id' => (int) $payment->business_id,
                'pump_operator_id' => (int) $payment->pump_operator_id,
                'shift_id' => (int) $payment->shift_id,
                'settlement_no' => $payment->settlement_no ?? null,
                'collection_form_no' => $payment->collection_form_no ?? null,
                'payment_type' => $type,
                'legacy_payment_type' => (string) $payment->payment_type,
                'gross_amount' => $gross,
                'discount_amount' => $discount,
                'net_amount' => $net,
                'source_type' => $payment->source_type ?? null,
                'source_id' => isset($payment->source_id) ? (int) $payment->source_id : null,
                'customer_id' => isset($payment->customer_id) ? (int) $payment->customer_id : null,
                'transaction_date' => $payment->transaction_date ?? null,
                'reference_no' => $payment->reference_no ?? null,
                'is_used' => (int) ($payment->is_used ?? 0),
                'parent_id' => isset($payment->parent_id) ? (int) $payment->parent_id : null,
            ];
        })->values();

        $this->detectDuplicateSourceIdentities($rows, $issues);
        $this->reconcileSupportingDetails(
            $businessId,
            $operatorId,
            $shiftIds,
            $rows,
            $requireDetailLinks,
            $issues
        );
        $this->reconcileOperationalDetails(
            $businessId,
            $operatorId,
            $shiftIds,
            $rows,
            $requireDetailLinks,
            $issues
        );

        // Historical/supporting-row differences are retained as warnings for
        // audit and repair, but they no longer stop finalization. Financial totals
        // continue to come only from the unique master payment rows above.
        $issues = array_map(fn (array $issue) => $this->normalizeIssueSeverity($issue), $issues);

        $totals = $this->summarizeUniquePayments($rows);
        $blocking = array_values(array_filter(
            $issues,
            fn (array $issue) => $this->isBlockingIssue($issue)
        ));

        $fingerprint = hash('sha256', json_encode([
            'business_id' => $businessId,
            'pump_operator_id' => $operatorId,
            'shift_ids' => $shiftIds,
            'payments' => $rows->map(fn ($row) => [
                $row->pump_payment_id,
                $row->payment_type,
                number_format((float) $row->net_amount, 4, '.', ''),
            ])->all(),
        ], JSON_UNESCAPED_SLASHES));

        return [
            'payments' => $rows,
            'payment_ids' => $rows->pluck('pump_payment_id')->map(fn ($id) => (int) $id)->all(),
            'totals' => $totals,
            'credit_summary' => $creditSummary,
            'issues' => array_values($issues),
            'blocking_issues' => $blocking,
            'has_blocking_issues' => ! empty($blocking),
            'fingerprint' => $fingerprint,
        ];
    }

    public function assertCanFinalize(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        ?int $settlementId = null,
        ?string $settlementNo = null,
        ?string $expectedFingerprint = null
    ): array {
        $snapshot = $this->build(
            $businessId,
            $operatorId,
            $shiftIds,
            $settlementId,
            $settlementNo,
            true
        );

        $expectedFingerprint = trim((string) $expectedFingerprint);
        if (! $this->fingerprintMatches($expectedFingerprint, (string) $snapshot['fingerprint'])) {
            $driftIssue = $this->issue(
                'payment_snapshot_changed_after_review',
                'critical',
                'Pumper Dashboard payment data changed after the Add Payment form was opened. Reload the form and review the current rows and totals before finalizing.',
                [
                    'expected_fingerprint' => $expectedFingerprint,
                    'current_fingerprint' => $snapshot['fingerprint'],
                    'payment_ids' => $snapshot['payment_ids'],
                ]
            );

            $this->recordIssues(
                $businessId,
                $operatorId,
                $shiftIds,
                $settlementId,
                $settlementNo,
                [$driftIssue],
                $snapshot['fingerprint']
            );

            Log::warning('PETROPD payment snapshot changed between review and finalization', [
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => $this->normalizeShiftIds($shiftIds),
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'expected_fingerprint' => $expectedFingerprint,
                'current_fingerprint' => $snapshot['fingerprint'],
                'payment_ids' => $snapshot['payment_ids'],
            ]);

            throw new RuntimeException(
                'Payment data changed after this Add Payment form was opened. The settlement was not finalized. Close and reopen Payment to Finalize, verify the current rows and totals, and finalize again.'
            );
        }

        $nonBlockingIssues = array_values(array_filter(
            $snapshot['issues'],
            fn (array $issue) => ! $this->isBlockingIssue($issue)
        ));

        if (! empty($nonBlockingIssues)) {
            // Preserve a complete audit trail while allowing the long-established
            // PetroPD settlement workflow to proceed from authoritative masters.
            $this->recordIssues(
                $businessId,
                $operatorId,
                $shiftIds,
                $settlementId,
                $settlementNo,
                $nonBlockingIssues,
                $snapshot['fingerprint']
            );

            Log::warning('PETROPD settlement finalized with legacy supporting-row warnings', [
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => $this->normalizeShiftIds($shiftIds),
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'fingerprint' => $snapshot['fingerprint'],
                'issues' => $nonBlockingIssues,
            ]);
        }

        if ($snapshot['has_blocking_issues']) {
            $this->recordIssues(
                $businessId,
                $operatorId,
                $shiftIds,
                $settlementId,
                $settlementNo,
                $snapshot['blocking_issues'],
                $snapshot['fingerprint']
            );

            Log::error('PETROPD settlement payment snapshot reconciliation failed', [
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => $this->normalizeShiftIds($shiftIds),
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'fingerprint' => $snapshot['fingerprint'],
                'issues' => $snapshot['blocking_issues'],
            ]);

            $firstIssue = $snapshot['blocking_issues'][0]['message']
                ?? 'The authoritative payment or settlement scope is invalid.';

            throw new RuntimeException(
                'Payment reconciliation failed. Settlement was not finalized. ' . $firstIssue
            );
        }

        return $snapshot;
    }

    /**
     * Blank expected fingerprints are accepted for backward-compatible callers.
     * When supplied, the fingerprint must match exactly so a concurrent payment
     * change cannot be finalized using stale rows and totals.
     */
    public function fingerprintMatches(?string $expectedFingerprint, string $currentFingerprint): bool
    {
        $expectedFingerprint = trim((string) $expectedFingerprint);
        if ($expectedFingerprint === '') {
            return true;
        }

        return hash_equals($expectedFingerprint, $currentFingerprint);
    }

    /**
     * Pure regression-testable total calculation. Duplicate query rows are
     * removed by the immutable pump_payment_id, never by amount.
     */
    public function summarizeUniquePayments(Collection $rows): array
    {
        return $this->calculateTotals(
            $rows->unique(fn ($row) => (int) ($row->pump_payment_id ?? 0))->values()
        );
    }

    private function calculateTotals(Collection $rows): array
    {
        $totals = [
            'cash' => 0.0,
            'card' => 0.0,
            'cheque' => 0.0,
            'credit_gross' => 0.0,
            'credit_discount' => 0.0,
            'credit' => 0.0,
            'other' => 0.0,
            'shortage' => 0.0,
            'excess' => 0.0,
            'recognized_total_paid' => 0.0,
        ];

        foreach ($rows as $row) {
            $type = $row->payment_type;
            $net = (float) $row->net_amount;

            if ($type === 'credit') {
                $totals['credit_gross'] += (float) $row->gross_amount;
                $totals['credit_discount'] += (float) $row->discount_amount;
                $totals['credit'] += $net;
            } elseif (array_key_exists($type, $totals)) {
                $totals[$type] += $net;
            }
        }

        $totals['recognized_total_paid'] =
            $totals['cash']
            + $totals['card']
            + $totals['cheque']
            + $totals['credit']
            + $totals['other']
            + $totals['shortage']
            + $totals['excess'];

        return array_map(fn ($value) => round((float) $value, 4), $totals);
    }

    private function reconcileSupportingDetails(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        Collection $payments,
        bool $requireDetailLinks,
        array &$issues
    ): void {
        foreach (self::DETAIL_TABLES as $type => $table) {
            $typePayments = $payments->where('payment_type', $type)->values();
            if ($typePayments->isEmpty()) {
                continue;
            }

            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'pump_payment_id')
                || ! Schema::hasColumn($table, 'shift_id')) {
                $issues[] = $this->issue(
                    'missing_detail_authority_columns',
                    'critical',
                    "{$table} must contain pump_payment_id and shift_id before PetroPD can reconcile {$type} payments.",
                    ['table' => $table, 'payment_type' => $type]
                );
                continue;
            }

            $paymentIds = $typePayments->pluck('pump_payment_id')->map(fn ($id) => (int) $id)->all();
            $amountExpression = $this->detailAmountExpression($table, $type);

            $select = [
                'pump_payment_id',
                DB::raw('COUNT(*) as detail_count'),
                DB::raw('MIN(COALESCE(business_id, 0)) as min_business_id'),
                DB::raw('MAX(COALESCE(business_id, 0)) as max_business_id'),
                DB::raw('MIN(COALESCE(shift_id, 0)) as min_shift_id'),
                DB::raw('MAX(COALESCE(shift_id, 0)) as max_shift_id'),
                DB::raw("MIN({$amountExpression}) as min_amount"),
                DB::raw("MAX({$amountExpression}) as max_amount"),
                DB::raw("SUM({$amountExpression}) as total_amount"),
            ];

            if (Schema::hasColumn($table, 'pump_operator_id')) {
                $select[] = DB::raw('MIN(COALESCE(pump_operator_id, 0)) as min_operator_id');
                $select[] = DB::raw('MAX(COALESCE(pump_operator_id, 0)) as max_operator_id');
            }

            $details = DB::table($table)
                ->whereIn('pump_payment_id', $paymentIds)
                ->groupBy('pump_payment_id')
                ->select($select)
                ->get()
                ->keyBy(fn ($row) => (int) $row->pump_payment_id);

            foreach ($typePayments as $payment) {
                $detail = $details->get((int) $payment->pump_payment_id);

                if (! $detail) {
                    if ($requireDetailLinks) {
                        $issues[] = $this->issue(
                            'missing_payment_detail_link',
                            'critical',
                            'A Pumper Dashboard payment has no settlement detail linked by pump_payment_id.',
                            [
                                'pump_payment_id' => $payment->pump_payment_id,
                                'payment_type' => $type,
                                'table' => $table,
                            ]
                        );
                    }
                    continue;
                }

                // A legacy/grouped credit payment may represent several saved
                // bills. Other payment types remain strictly one-to-one.
                if ($type !== 'credit' && (int) $detail->detail_count !== 1) {
                    $issues[] = $this->issue(
                        'multiple_payment_details_for_master',
                        'critical',
                        'More than one settlement detail is linked to the same Pump Operator Payment.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'detail_count' => (int) $detail->detail_count,
                        ]
                    );
                }

                if ((int) $detail->min_business_id !== $businessId
                    || (int) $detail->max_business_id !== $businessId
                    || ! in_array((int) $detail->min_shift_id, $shiftIds, true)
                    || (int) $detail->min_shift_id !== (int) $payment->shift_id
                    || (int) $detail->max_shift_id !== (int) $payment->shift_id) {
                    $issues[] = $this->issue(
                        'payment_detail_scope_mismatch',
                        'critical',
                        'A settlement detail does not match its master payment business or immutable Shift ID.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'master_shift_id' => $payment->shift_id,
                            'detail_min_shift_id' => (int) $detail->min_shift_id,
                            'detail_max_shift_id' => (int) $detail->max_shift_id,
                        ]
                    );
                }

                if (isset($detail->min_operator_id)
                    && ((int) $detail->min_operator_id !== $operatorId
                        || (int) $detail->max_operator_id !== $operatorId)) {
                    $issues[] = $this->issue(
                        'payment_detail_operator_mismatch',
                        'critical',
                        'A settlement detail Pump Operator does not match its master payment.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                        ]
                    );
                }

                $detailAmount = $type === 'credit'
                    ? (float) $detail->total_amount
                    : (float) $detail->max_amount;
                $detailAmountsConflict = $type !== 'credit'
                    && abs((float) $detail->max_amount - (float) $detail->min_amount) >= self::TOLERANCE;

                if ($detailAmountsConflict
                    || abs($detailAmount - (float) $payment->net_amount) >= self::TOLERANCE) {
                    $issues[] = $this->issue(
                        'payment_detail_amount_mismatch',
                        'critical',
                        'A settlement detail amount does not agree with the authoritative Pump Operator Payment amount.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'master_amount' => (float) $payment->net_amount,
                            'detail_min_amount' => (float) $detail->min_amount,
                            'detail_max_amount' => (float) $detail->max_amount,
                            'detail_total_amount' => (float) $detail->total_amount,
                        ]
                    );
                }
            }
        }
    }

    private function reconcileOperationalDetails(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        Collection $payments,
        bool $requireLinks,
        array &$issues
    ): void {
        foreach (self::OPERATIONAL_TABLES as $type => $config) {
            $typePayments = $payments->where('payment_type', $type)->values();
            if ($typePayments->isEmpty()) {
                continue;
            }

            $table = $config['table'];
            $amountColumn = $config['amount_column'];
            $operatorColumn = $config['operator_column'];
            $requiresShift = $config['requires_shift'] ?? true;
            $requiredForFinalization = $config['required_for_finalization'] ?? true;

            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'pump_payment_id')
                || ($requiresShift && ! Schema::hasColumn($table, 'shift_id'))
                || ! Schema::hasColumn($table, 'business_id')
                || ! Schema::hasColumn($table, $amountColumn)) {
                $issues[] = $this->issue(
                    'missing_operational_authority_columns',
                    'critical',
                    $requiresShift
                        ? "{$table} must link directly to pump_operator_payments by pump_payment_id and Shift ID."
                        : "{$table} must link directly to pump_operator_payments by pump_payment_id.",
                    ['table' => $table, 'payment_type' => $type]
                );
                continue;
            }

            $paymentIds = $typePayments->pluck('pump_payment_id')->map(fn ($id) => (int) $id)->all();
            $select = [
                'pump_payment_id',
                DB::raw('COUNT(*) as detail_count'),
                DB::raw('MIN(COALESCE(business_id, 0)) as min_business_id'),
                DB::raw('MAX(COALESCE(business_id, 0)) as max_business_id'),
                DB::raw('MIN(COALESCE(`' . $amountColumn . '`, 0)) as min_amount'),
                DB::raw('MAX(COALESCE(`' . $amountColumn . '`, 0)) as max_amount'),
                DB::raw('SUM(COALESCE(`' . $amountColumn . '`, 0)) as total_amount'),
            ];

            if ($requiresShift) {
                $select[] = DB::raw('MIN(COALESCE(shift_id, 0)) as min_shift_id');
                $select[] = DB::raw('MAX(COALESCE(shift_id, 0)) as max_shift_id');
            } else {
                $select[] = DB::raw('0 as min_shift_id');
                $select[] = DB::raw('0 as max_shift_id');
            }

            if (Schema::hasColumn($table, $operatorColumn)) {
                $select[] = DB::raw('MIN(COALESCE(`' . $operatorColumn . '`, 0)) as min_operator_id');
                $select[] = DB::raw('MAX(COALESCE(`' . $operatorColumn . '`, 0)) as max_operator_id');
            }

            $details = DB::table($table)
                ->whereIn('pump_payment_id', $paymentIds)
                ->groupBy('pump_payment_id')
                ->select($select)
                ->get()
                ->keyBy(fn ($row) => (int) $row->pump_payment_id);

            foreach ($typePayments as $payment) {
                $detail = $details->get((int) $payment->pump_payment_id);
                if (! $detail) {
                    if ($requireLinks && $requiredForFinalization) {
                        $issues[] = $this->issue(
                            'missing_operational_payment_link',
                            'critical',
                            'The authoritative payment has no linked operational supporting record.',
                            [
                                'pump_payment_id' => $payment->pump_payment_id,
                                'payment_type' => $type,
                                'table' => $table,
                            ]
                        );
                    }
                    continue;
                }

                // Grouped credit payments may have one Daily Voucher per bill.
                // Cash/card/cheque rows remain strictly one-to-one.
                if ($type !== 'credit' && (int) $detail->detail_count !== 1) {
                    $issues[] = $this->issue(
                        'multiple_operational_rows_for_master',
                        'critical',
                        'More than one operational supporting row is linked to the same authoritative payment.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'detail_count' => (int) $detail->detail_count,
                        ]
                    );
                }

                $scopeMismatch = (int) $detail->min_business_id !== $businessId
                    || (int) $detail->max_business_id !== $businessId;

                if ($requiresShift) {
                    $scopeMismatch = $scopeMismatch
                        || (int) $detail->min_shift_id !== (int) $payment->shift_id
                        || (int) $detail->max_shift_id !== (int) $payment->shift_id
                        || ! in_array((int) $detail->min_shift_id, $shiftIds, true);
                }

                if ($scopeMismatch) {
                    $issues[] = $this->issue(
                        'operational_payment_scope_mismatch',
                        'critical',
                        $requiresShift
                            ? 'An operational supporting row does not match the master payment business or immutable Shift ID.'
                            : 'An operational supporting row does not match the master payment business.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'master_shift_id' => $payment->shift_id,
                            'detail_min_shift_id' => (int) $detail->min_shift_id,
                            'detail_max_shift_id' => (int) $detail->max_shift_id,
                        ]
                    );
                }

                if (isset($detail->min_operator_id)
                    && ((int) $detail->min_operator_id !== $operatorId
                        || (int) $detail->max_operator_id !== $operatorId)) {
                    $issues[] = $this->issue(
                        'operational_payment_operator_mismatch',
                        'critical',
                        'An operational supporting row belongs to a different Pump Operator.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                        ]
                    );
                }

                $operationalAmount = $type === 'credit'
                    ? (float) $detail->total_amount
                    : (float) $detail->max_amount;
                $operationalAmountsConflict = $type !== 'credit'
                    && abs((float) $detail->max_amount - (float) $detail->min_amount) >= self::TOLERANCE;

                if ($operationalAmountsConflict
                    || abs($operationalAmount - (float) $payment->net_amount) >= self::TOLERANCE) {
                    $issues[] = $this->issue(
                        'operational_payment_amount_mismatch',
                        'critical',
                        'An operational supporting amount does not agree with the authoritative payment.',
                        [
                            'pump_payment_id' => $payment->pump_payment_id,
                            'payment_type' => $type,
                            'table' => $table,
                            'master_amount' => (float) $payment->net_amount,
                            'detail_min_amount' => (float) $detail->min_amount,
                            'detail_max_amount' => (float) $detail->max_amount,
                            'detail_total_amount' => (float) $detail->total_amount,
                        ]
                    );
                }
            }
        }
    }

    private function detectDuplicateSourceIdentities(Collection $payments, array &$issues): void
    {
        $duplicates = $payments
            ->filter(fn ($row) => ! empty($row->source_type) && ! empty($row->source_id))
            ->groupBy(fn ($row) => strtolower(trim((string) $row->source_type)) . '|' . (int) $row->source_id)
            ->filter(fn (Collection $rows) => $rows->count() > 1);

        foreach ($duplicates as $identity => $rows) {
            $issues[] = $this->issue(
                'duplicate_master_source_identity',
                'critical',
                'More than one master payment points to the same source transaction.',
                [
                    'source_identity' => $identity,
                    'pump_payment_ids' => $rows->pluck('pump_payment_id')->map(fn ($id) => (int) $id)->all(),
                ]
            );
        }
    }

    private function detailAmountExpression(string $table, string $type): string
    {
        if ($type === 'credit') {
            if (Schema::hasColumn($table, 'sub_total')) {
                return 'COALESCE(sub_total, amount - COALESCE(total_discount, 0))';
            }

            return 'COALESCE(amount, 0) - COALESCE(total_discount, 0)';
        }

        return 'COALESCE(amount, 0)';
    }

    private function matchesSettlement(string $stored, ?int $settlementId, ?string $settlementNo): bool
    {
        $allowed = array_values(array_filter([
            $settlementId !== null ? (string) $settlementId : null,
            $settlementNo !== null ? trim($settlementNo) : null,
        ], fn ($value) => $value !== null && $value !== ''));

        // A caller without a settlement identity is only reading a shift snapshot.
        if (empty($allowed)) {
            return true;
        }

        return in_array($stored, $allowed, true);
    }

    /**
     * Resolve the immutable Shift IDs owned by one active draft using the
     * strongest available evidence first. Payment links are authoritative;
     * assignment links are second; legacy work_shift values are last.
     */
    private function draftSettlementShiftIds(
        int $businessId,
        int $operatorId,
        int $settlementId,
        string $settlementNo,
        mixed $workShift
    ): array {
        $ownerKeys = array_values(array_unique(array_filter([
            (string) $settlementId,
            trim($settlementNo),
        ], fn ($value) => $value !== '')));

        // Pump Operator Payments are authoritative for payment ownership. If an
        // owner contains more than one Shift ID, normalizeLegacyDraftSettlementLinks
        // will reject the move rather than merging unrelated shifts.
        if (Schema::hasTable('pump_operator_payments')
            && Schema::hasColumn('pump_operator_payments', 'shift_id')
            && Schema::hasColumn('pump_operator_payments', 'settlement_no')
            && ! empty($ownerKeys)) {
            $paymentShiftIds = $this->normalizeShiftIds(
                DB::table('pump_operator_payments')
                    ->where('business_id', $businessId)
                    ->where('pump_operator_id', $operatorId)
                    ->whereIn('settlement_no', $ownerKeys)
                    ->pluck('shift_id')
                    ->all()
            );

            if (! empty($paymentShiftIds)) {
                return $paymentShiftIds;
            }
        }

        if (Schema::hasTable('pump_operator_assignments')) {
            $assignmentBase = DB::table('pump_operator_assignments')
                ->where('business_id', $businessId)
                ->where('pump_operator_id', $operatorId);

            if (Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
                $assignmentShiftIds = $this->normalizeShiftIds(
                    (clone $assignmentBase)
                        ->where('settlement_id', $settlementId)
                        ->pluck('shift_id')
                        ->all()
                );

                if (! empty($assignmentShiftIds)) {
                    return $assignmentShiftIds;
                }
            }
        }

        $rawWorkShiftValues = [];

        if (is_string($workShift)) {
            $decoded = json_decode($workShift, true);
            if (is_array($decoded)) {
                $workShift = $decoded;
            } else {
                $workShift = preg_split('/\s*,\s*/', trim($workShift), -1, PREG_SPLIT_NO_EMPTY);
            }
        }

        if (is_array($workShift)) {
            $rawWorkShiftValues = $workShift;
        } elseif ($workShift !== null && $workShift !== '') {
            $rawWorkShiftValues[] = $workShift;
        }

        $rawWorkShiftValues = array_values(array_unique(array_filter(array_map(
            'intval',
            $rawWorkShiftValues
        ))));

        if (empty($rawWorkShiftValues)) {
            return [];
        }

        // Newer drafts store Petro Shift IDs directly.
        if (Schema::hasTable('petro_shifts')) {
            $petroShiftQuery = DB::table('petro_shifts')->whereIn('id', $rawWorkShiftValues);

            if (Schema::hasColumn('petro_shifts', 'business_id')) {
                $petroShiftQuery->where('business_id', $businessId);
            }
            if (Schema::hasColumn('petro_shifts', 'pump_operator_id')) {
                $petroShiftQuery->where('pump_operator_id', $operatorId);
            }

            $directShiftIds = $this->normalizeShiftIds($petroShiftQuery->pluck('id')->all());
            if (! empty($directShiftIds)) {
                return $directShiftIds;
            }
        }

        // Old drafts may store the visible shift number instead.
        if (Schema::hasTable('pump_operator_assignments')
            && Schema::hasColumn('pump_operator_assignments', 'shift_number')) {
            return $this->normalizeShiftIds(
                DB::table('pump_operator_assignments')
                    ->where('business_id', $businessId)
                    ->where('pump_operator_id', $operatorId)
                    ->whereIn('shift_number', $rawWorkShiftValues)
                    ->pluck('shift_id')
                    ->all()
            );
        }

        return $this->normalizeShiftIds($rawWorkShiftValues);
    }

    /**
     * A finalized settlement reference is repairable only when the payment was
     * created after the old settlement was closed and the old settlement's own
     * non-payment evidence belongs to a different Shift.
     */
    private function isProvablyStaleFinalizedReference(
        object $owner,
        object $payment,
        int $businessId,
        int $operatorId,
        int $currentShiftId,
        int $currentSettlementId
    ): bool {
        if ($this->isActiveDraftSettlement($owner)
            || (int) ($owner->pump_operator_id ?? 0) !== $operatorId
            || (int) ($owner->id ?? 0) <= 0
            || (int) ($owner->id ?? 0) >= $currentSettlementId) {
            return false;
        }

        // Time is the strongest proof for a finalized owner: a payment created
        // after that settlement finished could never have been included in it.
        // Check this before legacy supporting rows, because those rows can carry
        // the same stale pump-operator settlement pointer as the master payment.
        $paymentCreatedAt = $payment->created_at ?? null;
        $ownerClosedAt = $owner->finalized_at
            ?? $owner->finalised_at
            ?? $owner->closed_at
            ?? $owner->completed_at
            ?? $owner->updated_at
            ?? $owner->finish_date
            ?? null;

        if (! empty($paymentCreatedAt) && ! empty($ownerClosedAt)) {
            try {
                $paymentTimestamp = \Carbon\Carbon::parse($paymentCreatedAt);
                $ownerTimestamp = \Carbon\Carbon::parse($ownerClosedAt);

                if ($paymentTimestamp->greaterThan($ownerTimestamp)) {
                    return true;
                }
            } catch (\Throwable) {
                // Continue to immutable Shift ownership proof below.
            }
        }

        $ownerShiftIds = $this->intrinsicSettlementShiftIds(
            $businessId,
            $operatorId,
            (int) $owner->id,
            (string) ($owner->settlement_no ?? ''),
            $owner->work_shift ?? null
        );

        // The immutable Shift ID is the strongest ownership evidence. Older
        // Pumper Dashboard code reused a stale settlement pointer while a new
        // shift was open. The payment could therefore be written before the old
        // settlement was finalized, which makes a timestamp-only check reject a
        // repair that is nevertheless provably safe. When the old settlement's
        // own assignment/meter/work_shift data identifies other shifts and does
        // not include the current one, it cannot legitimately own this payment.
        if (! empty($ownerShiftIds)) {
            return ! in_array($currentShiftId, $ownerShiftIds, true);
        }

        // Some copied/older tenant rows do not retain any intrinsic Shift
        // metadata. In that limited case, a payment created only after the old
        // settlement had already closed also cannot have been part of that
        // finalized settlement. Keep the fallback deliberately strict.
        $ownerClosedAt = $owner->updated_at ?? $owner->created_at ?? null;
        if (empty($paymentCreatedAt) || empty($ownerClosedAt)) {
            return false;
        }

        try {
            $paymentTimestamp = \Carbon\Carbon::parse($paymentCreatedAt);
            $ownerTimestamp = \Carbon\Carbon::parse($ownerClosedAt);
        } catch (\Throwable) {
            return false;
        }

        return $paymentTimestamp->greaterThan($ownerTimestamp);
    }

    /**
     * Resolve settlement Shift ownership without trusting payment links. This is
     * used only to prove that a finalized owner cannot legitimately own a newly
     * created payment.
     */
    private function intrinsicSettlementShiftIds(
        int $businessId,
        int $operatorId,
        int $settlementId,
        string $settlementNo,
        mixed $workShift
    ): array {
        $shiftIds = [];

        if (Schema::hasTable('pump_operator_assignments')
            && Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            $shiftIds = array_merge(
                $shiftIds,
                DB::table('pump_operator_assignments')
                    ->where('business_id', $businessId)
                    ->where('pump_operator_id', $operatorId)
                    ->where('settlement_id', $settlementId)
                    ->pluck('shift_id')
                    ->all()
            );
        }

        $ownerKeys = array_values(array_unique(array_filter([
            (string) $settlementId,
            trim($settlementNo),
        ], fn ($value) => $value !== '')));

        foreach (['pump_operator_meter_sales', 'pump_operator_other_sales'] as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'settlement_no')
                || ! Schema::hasColumn($table, 'shift_id')) {
                continue;
            }

            $query = DB::table($table)->whereIn('settlement_no', $ownerKeys);
            if (Schema::hasColumn($table, 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn($table, 'pump_operator_id')) {
                $query->where('pump_operator_id', $operatorId);
            }
            $shiftIds = array_merge($shiftIds, $query->pluck('shift_id')->all());
        }

        $rawWorkShiftValues = [];
        if (is_string($workShift)) {
            $decoded = json_decode($workShift, true);
            $workShift = is_array($decoded)
                ? $decoded
                : preg_split('/\s*,\s*/', trim($workShift), -1, PREG_SPLIT_NO_EMPTY);
        }
        if (is_array($workShift)) {
            $rawWorkShiftValues = $workShift;
        } elseif ($workShift !== null && $workShift !== '') {
            $rawWorkShiftValues = [$workShift];
        }
        $rawWorkShiftValues = $this->normalizeShiftIds($rawWorkShiftValues);

        if (! empty($rawWorkShiftValues)) {
            $directShiftIds = [];
            if (Schema::hasTable('petro_shifts')) {
                $query = DB::table('petro_shifts')->whereIn('id', $rawWorkShiftValues);
                if (Schema::hasColumn('petro_shifts', 'business_id')) {
                    $query->where('business_id', $businessId);
                }
                if (Schema::hasColumn('petro_shifts', 'pump_operator_id')) {
                    $query->where('pump_operator_id', $operatorId);
                }
                $directShiftIds = $query->pluck('id')->all();
            }

            if (! empty($directShiftIds)) {
                $shiftIds = array_merge($shiftIds, $directShiftIds);
            } elseif (Schema::hasTable('pump_operator_assignments')
                && Schema::hasColumn('pump_operator_assignments', 'shift_number')) {
                $shiftIds = array_merge(
                    $shiftIds,
                    DB::table('pump_operator_assignments')
                        ->where('business_id', $businessId)
                        ->where('pump_operator_id', $operatorId)
                        ->whereIn('shift_number', $rawWorkShiftValues)
                        ->pluck('shift_id')
                        ->all()
                );
            } else {
                $shiftIds = array_merge($shiftIds, $rawWorkShiftValues);
            }
        }

        return $this->normalizeShiftIds($shiftIds);
    }

    /**
     * Keep the supporting rows aligned with the authoritative payment after a
     * safe ownership repair. Every update is restricted by pump_payment_id and
     * the exact tenant/operator/Shift scope.
     */
    private function relinkPaymentSupportingRows(
        int $paymentId,
        int $businessId,
        int $operatorId,
        int $shiftId,
        int $settlementId,
        string $settlementNo,
        string $oldSettlementReference,
        ?object $oldOwner
    ): void {
        $oldKeys = array_values(array_unique(array_filter([
            $oldSettlementReference,
            isset($oldOwner->id) ? (string) $oldOwner->id : null,
            isset($oldOwner->settlement_no) ? trim((string) $oldOwner->settlement_no) : null,
        ], fn ($value) => $value !== null && $value !== '')));

        $numericSettlementTables = [
            'settlement_cash_payments',
            'settlement_card_payments',
            'settlement_cheque_payments',
            'settlement_shortage_payments',
            'settlement_excess_payments',
        ];

        foreach ($numericSettlementTables as $table) {
            $this->updateSupportingSettlementReference(
                $table,
                $paymentId,
                $businessId,
                $operatorId,
                $shiftId,
                'settlement_no',
                (string) $settlementId,
                $oldKeys
            );
        }

        foreach ([
            'settlement_credit_sale_payments',
            'daily_cards',
            'daily_cheque_payments',
            'daily_vouchers',
            'pump_operator_meter_sales',
        ] as $table) {
            $this->updateSupportingSettlementReference(
                $table,
                $paymentId,
                $businessId,
                $operatorId,
                $shiftId,
                'settlement_no',
                $settlementNo,
                $oldKeys
            );
        }

        if (Schema::hasTable('daily_collections')
            && Schema::hasColumn('daily_collections', 'pump_payment_id')
            && Schema::hasColumn('daily_collections', 'settlement_id')) {
            $query = DB::table('daily_collections')->where('pump_payment_id', $paymentId);
            if (Schema::hasColumn('daily_collections', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            if (Schema::hasColumn('daily_collections', 'pump_operator_id')) {
                $query->where('pump_operator_id', $operatorId);
            }
            if (Schema::hasColumn('daily_collections', 'shift_id')) {
                $query->where('shift_id', $shiftId);
            }

            $query->where(function ($ownerQuery) use ($oldKeys, $settlementId) {
                $ownerQuery->whereNull('settlement_id')
                    ->orWhere('settlement_id', $settlementId);
                foreach ($oldKeys as $oldKey) {
                    if (ctype_digit((string) $oldKey)) {
                        $ownerQuery->orWhere('settlement_id', (int) $oldKey);
                    }
                }
            })->update(['settlement_id' => $settlementId]);
        }
    }

    private function updateSupportingSettlementReference(
        string $table,
        int $paymentId,
        int $businessId,
        int $operatorId,
        int $shiftId,
        string $referenceColumn,
        string $newReference,
        array $oldReferences
    ): void {
        if (! Schema::hasTable($table)
            || ! Schema::hasColumn($table, 'pump_payment_id')
            || ! Schema::hasColumn($table, $referenceColumn)) {
            return;
        }

        $query = DB::table($table)->where('pump_payment_id', $paymentId);
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }
        if (Schema::hasColumn($table, 'pump_operator_id')) {
            $query->where('pump_operator_id', $operatorId);
        } elseif (Schema::hasColumn($table, 'operator_id')) {
            $query->where('operator_id', $operatorId);
        }
        if (Schema::hasColumn($table, 'shift_id')) {
            $query->where('shift_id', $shiftId);
        }

        $query->where(function ($ownerQuery) use ($referenceColumn, $newReference, $oldReferences) {
            $ownerQuery->whereNull($referenceColumn)
                ->orWhere($referenceColumn, '')
                ->orWhere($referenceColumn, $newReference);
            foreach ($oldReferences as $oldReference) {
                $ownerQuery->orWhere($referenceColumn, (string) $oldReference);
            }
        })->update([$referenceColumn => $newReference]);
    }

    /**
     * The application uses status=1 for an editable draft and status=0 for a
     * finalized settlement. Respect newer explicit finalization flags as well.
     */
    private function isActiveDraftSettlement(object $settlement): bool
    {
        foreach ([
            'is_finalized',
            'is_finalised',
            'finalized',
            'finalised',
            'is_closed',
            'closed',
            'is_completed',
            'completed',
        ] as $flag) {
            if (property_exists($settlement, $flag)
                && in_array(strtolower(trim((string) ($settlement->{$flag} ?? ''))), ['1', 'true', 'yes'], true)) {
                return false;
            }
        }

        $status = strtolower(trim((string) ($settlement->status ?? '')));
        if (in_array($status, ['1', 'draft', 'active', 'open', 'pending'], true)) {
            return true;
        }
        if (in_array($status, ['finalized', 'finalised', 'closed', 'completed', 'complete', 'approved', 'posted'], true)) {
            return false;
        }

        // Historical copies do not use status=0 consistently: current PD
        // finalization writes status=0 together with finish_date and closed
        // assignments, while some older saved drafts also retained status=0.
        // Distinguish them by irreversible finalization evidence instead of
        // treating every zero-status row as already posted.
        if ($status === '0') {
            foreach (['finish_date', 'finalized_at', 'finalised_at', 'closed_at', 'completed_at'] as $dateColumn) {
                if (property_exists($settlement, $dateColumn)
                    && ! empty($settlement->{$dateColumn})) {
                    return false;
                }
            }

            $settlementId = (int) ($settlement->id ?? 0);
            if ($settlementId > 0
                && Schema::hasTable('pump_operator_assignments')
                && Schema::hasColumn('pump_operator_assignments', 'settlement_id')
                && Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
                $assignmentClosed = DB::table('pump_operator_assignments')
                    ->where('settlement_id', $settlementId)
                    ->where('closed_in_settlement', 1)
                    ->exists();
                if ($assignmentClosed) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));
        return self::TYPE_ALIASES[$type] ?? $type;
    }

    private function normalizeShiftIds(array $shiftIds): array
    {
        return array_values(array_unique(array_filter(
            array_map('intval', $shiftIds),
            fn ($id) => $id > 0
        )));
    }

    private function nullableFloat($value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private function issue(string $type, string $severity, string $message, array $context = []): array
    {
        return array_merge([
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
        ], $context);
    }

    private function normalizeIssueSeverity(array $issue): array
    {
        $type = (string) ($issue['type'] ?? '');
        if (in_array($type, self::NON_BLOCKING_SUPPORT_ISSUES, true)) {
            $issue['severity'] = 'warning';
        } else {
            $issue['severity'] = $issue['severity'] ?? 'critical';
        }

        return $issue;
    }

    private function isBlockingIssue(array $issue): bool
    {
        return ($issue['severity'] ?? 'critical') === 'critical'
            && ! in_array((string) ($issue['type'] ?? ''), self::NON_BLOCKING_SUPPORT_ISSUES, true);
    }

    private function emptySnapshot(array $issues = []): array
    {
        $issues = array_map(fn (array $issue) => $this->normalizeIssueSeverity($issue), $issues);
        $blocking = array_values(array_filter(
            $issues,
            fn (array $issue) => $this->isBlockingIssue($issue)
        ));

        return [
            'payments' => collect(),
            'payment_ids' => [],
            'totals' => [
                'cash' => 0.0,
                'card' => 0.0,
                'cheque' => 0.0,
                'credit_gross' => 0.0,
                'credit_discount' => 0.0,
                'credit' => 0.0,
                'other' => 0.0,
                'shortage' => 0.0,
                'excess' => 0.0,
                'recognized_total_paid' => 0.0,
            ],
            'credit_summary' => $this->creditAuthority->creditSummary(0, 0, []),
            'issues' => array_values($issues),
            'blocking_issues' => $blocking,
            'has_blocking_issues' => ! empty($blocking),
            'fingerprint' => hash('sha256', 'empty'),
        ];
    }

    private function recordIssues(
        int $businessId,
        int $operatorId,
        array $shiftIds,
        ?int $settlementId,
        ?string $settlementNo,
        array $issues,
        string $fingerprint
    ): void {
        if (! Schema::hasTable('petro_pd_payment_reconciliation_events')) {
            return;
        }

        $hasOperationalColumns = Schema::hasColumn('petro_pd_payment_reconciliation_events', 'first_seen_at')
            && Schema::hasColumn('petro_pd_payment_reconciliation_events', 'last_seen_at')
            && Schema::hasColumn('petro_pd_payment_reconciliation_events', 'occurrence_count');
        $now = now();

        foreach ($issues as $issue) {
            $paymentId = isset($issue['pump_payment_id']) ? (int) $issue['pump_payment_id'] : null;
            // Stable event identity: the visible settlement number is the lifecycle
            // identity when available. A draft receiving its numeric settlement ID later
            // must not create a second event for the same Shift/payment issue.
            $normalizedSettlementNo = trim((string) ($settlementNo ?? ''));
            $settlementKey = $normalizedSettlementNo !== ''
                ? 'NO:' . $normalizedSettlementNo
                : ($settlementId !== null ? 'ID:' . $settlementId : 'SHIFT_SCOPE');

            $eventHash = hash('sha256', json_encode([
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => $this->normalizeShiftIds($shiftIds),
                'settlement_key' => $settlementKey,
                'issue_type' => $issue['type'] ?? 'unknown',
                'pump_payment_id' => $paymentId,
            ], JSON_UNESCAPED_SLASHES));

            $payload = [
                'business_id' => $businessId,
                'pump_operator_id' => $operatorId,
                'shift_ids' => implode(',', $this->normalizeShiftIds($shiftIds)),
                'settlement_id' => $settlementId,
                'settlement_no' => $settlementNo,
                'pump_payment_id' => $paymentId,
                'issue_type' => $issue['type'] ?? 'unknown',
                'severity' => $issue['severity'] ?? 'critical',
                'message' => $issue['message'] ?? 'Payment reconciliation issue',
                'context_json' => json_encode($issue, JSON_UNESCAPED_SLASHES),
                'snapshot_fingerprint' => $fingerprint,
                'resolved_at' => null,
                'updated_at' => $now,
            ];

            $normalizedShiftScope = implode(',', $this->normalizeShiftIds($shiftIds));
            $existing = DB::table('petro_pd_payment_reconciliation_events')
                ->where('event_hash', $eventHash)
                ->first();

            // Parcel 1 used the full snapshot fingerprint in event_hash. Adopt an
            // existing unresolved legacy event with the same immutable scope so
            // deployment of Parcel 2 does not create a second open event.
            if (! $existing) {
                $legacyEventQuery = DB::table('petro_pd_payment_reconciliation_events')
                    ->where('business_id', $businessId)
                    ->where('pump_operator_id', $operatorId)
                    ->where('shift_ids', $normalizedShiftScope)
                    ->where('issue_type', $issue['type'] ?? 'unknown')
                    ->whereNull('resolved_at');

                if ($normalizedSettlementNo !== '') {
                    // Settlement number survives the draft-to-saved lifecycle; do not
                    // require the numeric ID to have been present at first detection.
                    $legacyEventQuery->where('settlement_no', $normalizedSettlementNo);
                } elseif ($settlementId !== null) {
                    $legacyEventQuery->where('settlement_id', $settlementId)
                        ->where(function ($query) {
                            $query->whereNull('settlement_no')->orWhere('settlement_no', '');
                        });
                } else {
                    $legacyEventQuery->whereNull('settlement_id')
                        ->where(function ($query) {
                            $query->whereNull('settlement_no')->orWhere('settlement_no', '');
                        });
                }

                $paymentId === null
                    ? $legacyEventQuery->whereNull('pump_payment_id')
                    : $legacyEventQuery->where('pump_payment_id', $paymentId);

                $existing = $legacyEventQuery->orderByDesc('id')->first();
            }

            if ($existing) {
                $payload['event_hash'] = $eventHash;
                if ($hasOperationalColumns) {
                    $payload['last_seen_at'] = $now;
                    $payload['occurrence_count'] = DB::raw('COALESCE(occurrence_count, 1) + 1');
                }

                // A recurring issue is open again even if it had previously been resolved.
                if (Schema::hasColumn('petro_pd_payment_reconciliation_events', 'resolved_by')) {
                    $payload['resolved_by'] = null;
                }
                if (Schema::hasColumn('petro_pd_payment_reconciliation_events', 'resolution_note')) {
                    $payload['resolution_note'] = null;
                }

                DB::table('petro_pd_payment_reconciliation_events')
                    ->where('id', (int) $existing->id)
                    ->update($payload);
                continue;
            }

            $payload['event_hash'] = $eventHash;
            $payload['created_at'] = $now;
            if ($hasOperationalColumns) {
                $payload['first_seen_at'] = $now;
                $payload['last_seen_at'] = $now;
                $payload['occurrence_count'] = 1;
            }

            DB::table('petro_pd_payment_reconciliation_events')->insert($payload);
        }
    }
}
