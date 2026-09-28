<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Historical audit and conservative repair for Pumper Dashboard payments.
 *
 * It never guesses an ambiguous payment and never deletes financial rows.
 * Apply mode performs only these safe operations:
 * - link an unlinked detail/operational row when exactly one master matches;
 * - fill a NULL/zero detail Shift/operator from its linked master;
 * - fill a NULL/zero master Shift when every linked supporting row agrees on
 *   exactly one Shift and operator/business scope also agrees.
 *
 * Conflicting non-zero Shift IDs, duplicate supporting rows and amount
 * mismatches are recorded for controlled review and remain blocking issues.
 */
class PetroPdHistoricalPaymentRepairService
{
    private const MASTER_TYPES = [
        'cash' => ['cash'],
        'card' => ['card', 'cards'],
        'cheque' => ['cheque', 'cheques'],
        'credit' => ['credit', 'multiple_credit'],
        'other' => ['other'],
        'shortage' => ['shortage'],
        'excess' => ['excess'],
    ];

    private const TABLES = [
        'settlement_cash_payments' => [
            'type' => 'cash', 'operator' => 'pump_operator_id', 'collection' => 'collection_form_no',
            'amount' => ['net' => 'amount'],
        ],
        'settlement_card_payments' => [
            'type' => 'card', 'operator' => 'pump_operator_id', 'collection' => null,
            'amount' => ['net' => 'amount'],
        ],
        'settlement_cheque_payments' => [
            'type' => 'cheque', 'operator' => 'pump_operator_id', 'collection' => 'collection_form_no',
            'amount' => ['net' => 'amount'],
        ],
        'settlement_credit_sale_payments' => [
            'type' => 'credit', 'operator' => 'pump_operator_id', 'collection' => 'collection_form_no',
            'amount' => ['gross' => 'amount', 'discount' => 'total_discount', 'net' => 'sub_total'],
        ],
        'settlement_shortage_payments' => [
            'type' => 'shortage', 'operator' => 'pump_operator_id', 'collection' => null,
            'amount' => ['net' => 'amount'],
        ],
        'settlement_excess_payments' => [
            'type' => 'excess', 'operator' => 'pump_operator_id', 'collection' => null,
            'amount' => ['net' => 'amount'],
        ],
        'daily_collections' => [
            'type' => 'cash', 'operator' => 'pump_operator_id', 'collection' => 'collection_form_no',
        ],
        'daily_cards' => [
            'type' => 'card', 'operator' => 'pump_operator_id', 'collection' => 'collection_no',
        ],
        'daily_cheque_payments' => [
            'type' => 'cheque', 'operator' => 'pump_operator_id', 'collection' => 'collection_form_no',
        ],
        'daily_vouchers' => [
            'type' => 'credit', 'operator' => 'operator_id', 'collection' => 'daily_vouchers_no',
        ],
    ];

    private array $summary = [];
    private ?int $runId = null;
    private bool $apply = false;
    private array $filters = [];

    /**
     * @param array{business_id?:int|null,shift_id?:int|null,settlement_no?:string|null,apply?:bool,tenant_reference?:string|null} $options
     */
    public function run(array $options = []): array
    {
        $this->apply = (bool) ($options['apply'] ?? false);
        $this->filters = [
            'business_id' => ! empty($options['business_id']) ? (int) $options['business_id'] : null,
            'shift_id' => ! empty($options['shift_id']) ? (int) $options['shift_id'] : null,
            'settlement_no' => isset($options['settlement_no']) && $options['settlement_no'] !== ''
                ? (string) $options['settlement_no']
                : null,
        ];
        $this->summary = [
            'database' => DB::connection()->getDatabaseName(),
            'apply_changes' => $this->apply,
            'scanned_rows' => 0,
            'issues_found' => 0,
            'critical_issues' => 0,
            'warnings' => 0,
            'safe_repairs_found' => 0,
            'repairs_applied' => 0,
            'unlinked_rows' => 0,
            'ambiguous_rows' => 0,
            'scope_mismatches' => 0,
            'amount_mismatches' => 0,
            'duplicate_supporting_rows' => 0,
            'missing_master_shift' => 0,
            'tables' => [],
        ];

        $this->startRun($options);

        try {
            if (! Schema::hasTable('pump_operator_payments')) {
                throw new \RuntimeException('pump_operator_payments table does not exist.');
            }

            $this->scanMasterPayments();
            foreach (self::TABLES as $table => $config) {
                $this->scanSupportingTable($table, $config);
            }
            $this->repairMissingMasterShiftsFromLinkedDetails();
            $this->scanDuplicateSourceIdentities();

            $this->finishRun('completed');
            return $this->summary;
        } catch (Throwable $e) {
            $this->summary['error'] = $e->getMessage();
            $this->finishRun('failed', $e->getMessage());
            Log::error('PetroPD historical payment repair failed', [
                'options' => $options,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function scanMasterPayments(): void
    {
        $query = DB::table('pump_operator_payments')
            ->whereIn(DB::raw('LOWER(payment_type)'), $this->allPaymentTypeAliases());
        $this->applyMasterFilters($query);

        $query->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                $this->summary['scanned_rows']++;

                if (empty($row->shift_id)) {
                    $this->summary['missing_master_shift']++;
                    $this->issue(
                        'pump_operator_payments',
                        (int) $row->id,
                        (int) $row->id,
                        null,
                        'master_missing_shift_id',
                        'critical',
                        'Authoritative Pump Operator Payment has no immutable Shift ID.',
                        (array) $row
                    );
                }
            }
        }, 'id');
    }

    private function scanSupportingTable(string $table, array $config): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return;
        }

        $tableSummary = [
            'scanned' => 0,
            'unlinked' => 0,
            'safe_repairs' => 0,
            'applied' => 0,
            'issues' => 0,
        ];

        $query = DB::table($table);
        $this->applySupportingFilters($query, $table, $config);

        $query->orderBy('id')->chunkById(500, function ($rows) use ($table, $config, &$tableSummary) {
            foreach ($rows as $row) {
                $tableSummary['scanned']++;
                $this->summary['scanned_rows']++;

                $pumpPaymentId = Schema::hasColumn($table, 'pump_payment_id')
                    ? (int) ($row->pump_payment_id ?? 0)
                    : 0;

                if ($pumpPaymentId <= 0) {
                    $tableSummary['unlinked']++;
                    $this->summary['unlinked_rows']++;
                    $resolution = $this->resolveMasterForSupportingRow($table, $config, $row);

                    if ($resolution['count'] === 1) {
                        $master = $resolution['master'];

                        // A one-to-one supporting table must not claim a master
                        // already linked to another row. For legacy scoped
                        // matching, more than one unlinked row with the same
                        // scope/form is also ambiguous; do not choose the first.
                        $existingLinked = Schema::hasColumn($table, 'pump_payment_id')
                            && DB::table($table)
                                ->where('pump_payment_id', $master->id)
                                ->where('id', '<>', $row->id)
                                ->exists();
                        $competingUnlinked = ($resolution['strategy'] ?? null) === 'scoped_unique'
                            ? $this->countCompetingUnlinkedRows($table, $config, $row)
                            : 1;

                        if ($existingLinked || $competingUnlinked > 1) {
                            $tableSummary['issues']++;
                            $this->summary['ambiguous_rows']++;
                            $this->issue(
                                $table,
                                (int) $row->id,
                                (int) $master->id,
                                $this->rowShiftId($table, $row),
                                'supporting_link_not_one_to_one',
                                'critical',
                                'The candidate master is already linked or several supporting rows compete for it. No row was linked automatically.',
                                [
                                    'row' => (array) $row,
                                    'existing_linked_row' => $existingLinked,
                                    'competing_unlinked_rows' => $competingUnlinked,
                                    'candidate_master_id' => (int) $master->id,
                                ]
                            );
                            continue;
                        }

                        $after = $this->safeLinkPayload($table, $config, $row, $master);
                        $tableSummary['safe_repairs']++;
                        $this->summary['safe_repairs_found']++;

                        $applied = false;
                        if ($this->apply) {
                            $applied = $this->applySafeUpdate($table, (int) $row->id, $after);
                            if ($applied) {
                                $tableSummary['applied']++;
                                $this->summary['repairs_applied']++;
                            }
                        }

                        $this->action(
                            $table,
                            (int) $row->id,
                            (int) $master->id,
                            (int) $master->shift_id,
                            'link_unambiguous_master',
                            'warning',
                            true,
                            $applied,
                            'Exactly one authoritative Pump Operator Payment matched this supporting row.',
                            (array) $row,
                            $after
                        );
                    } elseif ($resolution['count'] > 1) {
                        $tableSummary['issues']++;
                        $this->summary['ambiguous_rows']++;
                        $this->issue(
                            $table,
                            (int) $row->id,
                            null,
                            $this->rowShiftId($table, $row),
                            'ambiguous_master_link',
                            'critical',
                            'More than one Pump Operator Payment matches this supporting row. No link was changed.',
                            ['row' => (array) $row, 'candidate_ids' => $resolution['candidate_ids']]
                        );
                    } else {
                        $tableSummary['issues']++;
                        $this->issue(
                            $table,
                            (int) $row->id,
                            null,
                            $this->rowShiftId($table, $row),
                            'missing_master_link',
                            'critical',
                            'No authoritative Pump Operator Payment could be matched safely.',
                            (array) $row
                        );
                    }

                    continue;
                }

                $master = DB::table('pump_operator_payments')->where('id', $pumpPaymentId)->first();
                if (! $master) {
                    $tableSummary['issues']++;
                    $this->issue(
                        $table,
                        (int) $row->id,
                        $pumpPaymentId,
                        $this->rowShiftId($table, $row),
                        'linked_master_missing',
                        'critical',
                        'Supporting row points to a Pump Operator Payment that does not exist.',
                        (array) $row
                    );
                    continue;
                }

                $this->validateLinkedScope($table, $config, $row, $master, $tableSummary);
            }
        }, 'id');

        if (Schema::hasColumn($table, 'pump_payment_id')) {
            $duplicateQuery = DB::table($table)
                ->whereNotNull('pump_payment_id')
                ->where('pump_payment_id', '>', 0)
                ->select('business_id', 'pump_payment_id', DB::raw('COUNT(*) as row_count'), DB::raw('GROUP_CONCAT(id ORDER BY id) as row_ids'))
                ->groupBy('business_id', 'pump_payment_id')
                ->havingRaw('COUNT(*) > 1');
            $this->applySupportingFilters($duplicateQuery, $table, $config);

            foreach ($duplicateQuery->get() as $duplicate) {
                $tableSummary['issues']++;
                $this->summary['duplicate_supporting_rows']++;
                $this->issue(
                    $table,
                    null,
                    (int) $duplicate->pump_payment_id,
                    null,
                    'multiple_supporting_rows_for_master',
                    'critical',
                    'More than one supporting row is linked to the same authoritative payment. Rows were not deleted automatically.',
                    ['row_ids' => $duplicate->row_ids, 'row_count' => (int) $duplicate->row_count]
                );
            }
        }

        $this->summary['tables'][$table] = $tableSummary;
    }

    private function resolveMasterForSupportingRow(string $table, array $config, object $row): array
    {
        $typeAliases = self::MASTER_TYPES[$config['type']];
        $candidateIds = [];

        // Strongest existing legacy relationships.
        foreach (['linked_payment_id'] as $directColumn) {
            if (Schema::hasColumn($table, $directColumn) && ! empty($row->{$directColumn})) {
                $master = DB::table('pump_operator_payments')
                    ->where('id', (int) $row->{$directColumn})
                    ->whereIn(DB::raw('LOWER(payment_type)'), $typeAliases)
                    ->first();
                if ($master) {
                    return ['count' => 1, 'master' => $master, 'candidate_ids' => [(int) $master->id], 'strategy' => 'direct_column'];
                }
            }
        }

        // parent_id was the historical detail link for settlement payment rows.
        $parentCandidates = DB::table('pump_operator_payments')
            ->where('parent_id', (int) $row->id)
            ->whereIn(DB::raw('LOWER(payment_type)'), $typeAliases);
        $this->applyRowScopeToMasterQuery($parentCandidates, $table, $config, $row, false);
        $parentRows = $parentCandidates->limit(3)->get();
        if ($parentRows->count() === 1) {
            return ['count' => 1, 'master' => $parentRows->first(), 'candidate_ids' => [(int) $parentRows->first()->id], 'strategy' => 'parent_id'];
        }
        if ($parentRows->count() > 1) {
            return ['count' => $parentRows->count(), 'master' => null, 'candidate_ids' => $parentRows->pluck('id')->map(fn ($id) => (int) $id)->all()];
        }

        $query = DB::table('pump_operator_payments')
            ->whereIn(DB::raw('LOWER(payment_type)'), $typeAliases);
        $this->applyRowScopeToMasterQuery($query, $table, $config, $row, true);

        $candidates = $query->orderBy('id')->limit(3)->get();
        return [
            'count' => $candidates->count(),
            'master' => $candidates->count() === 1 ? $candidates->first() : null,
            'candidate_ids' => $candidates->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'strategy' => 'scoped_unique',
        ];
    }

    /**
     * Count unlinked supporting rows that share the exact legacy identity used
     * for a scoped match. More than one means the relation is not safe to infer.
     */
    private function countCompetingUnlinkedRows(string $table, array $config, object $row): int
    {
        if (! Schema::hasColumn($table, 'pump_payment_id')) {
            return 1;
        }

        $query = DB::table($table)->where(function ($q) {
            $q->whereNull('pump_payment_id')->orWhere('pump_payment_id', 0);
        });

        if (Schema::hasColumn($table, 'business_id') && ! empty($row->business_id)) {
            $query->where('business_id', (int) $row->business_id);
        }
        $operatorColumn = $config['operator'];
        if ($operatorColumn && Schema::hasColumn($table, $operatorColumn) && ! empty($row->{$operatorColumn})) {
            $query->where($operatorColumn, (int) $row->{$operatorColumn});
        }
        if (Schema::hasColumn($table, 'shift_id') && ! empty($row->shift_id)) {
            $query->where('shift_id', (int) $row->shift_id);
        }
        $collectionColumn = $config['collection'];
        if ($collectionColumn && Schema::hasColumn($table, $collectionColumn)) {
            $collection = trim((string) ($row->{$collectionColumn} ?? ''));
            if ($collection === '') {
                return 2;
            }
            $query->whereRaw('CAST(' . $collectionColumn . ' AS CHAR) = ?', [$collection]);
        } else {
            return 1;
        }

        return (int) $query->count();
    }

    private function applyRowScopeToMasterQuery(
        Builder $query,
        string $table,
        array $config,
        object $row,
        bool $requireCollection
    ): void {
        if (Schema::hasColumn($table, 'business_id') && ! empty($row->business_id)) {
            $query->where('business_id', (int) $row->business_id);
        }

        $operatorColumn = $config['operator'];
        if ($operatorColumn && Schema::hasColumn($table, $operatorColumn) && ! empty($row->{$operatorColumn})) {
            $query->where('pump_operator_id', (int) $row->{$operatorColumn});
        }

        if (Schema::hasColumn($table, 'shift_id') && ! empty($row->shift_id)) {
            $query->where('shift_id', (int) $row->shift_id);
        }

        $collectionColumn = $config['collection'];
        if ($collectionColumn && Schema::hasColumn($table, $collectionColumn)) {
            $collection = trim((string) ($row->{$collectionColumn} ?? ''));
            if ($collection !== '') {
                $query->whereRaw('CAST(collection_form_no AS CHAR) = ?', [$collection]);
            } elseif ($requireCollection) {
                $query->whereRaw('1 = 0');
            }
        } elseif ($requireCollection) {
            // Tables without a collection identity must resolve through a direct
            // or parent relationship; never match by amount alone.
            $query->whereRaw('1 = 0');
        }
    }

    private function safeLinkPayload(string $table, array $config, object $row, object $master): array
    {
        $payload = [];
        if (Schema::hasColumn($table, 'pump_payment_id')) {
            $payload['pump_payment_id'] = (int) $master->id;
        }
        if (Schema::hasColumn($table, 'shift_id') && empty($row->shift_id)) {
            $payload['shift_id'] = (int) $master->shift_id;
        }
        if (Schema::hasColumn($table, 'business_id') && empty($row->business_id)) {
            $payload['business_id'] = (int) $master->business_id;
        }
        $operatorColumn = $config['operator'];
        if ($operatorColumn && Schema::hasColumn($table, $operatorColumn) && empty($row->{$operatorColumn})) {
            $payload[$operatorColumn] = (int) $master->pump_operator_id;
        }

        return $payload;
    }

    private function validateLinkedScope(
        string $table,
        array $config,
        object $row,
        object $master,
        array &$tableSummary
    ): void {
        $safePayload = [];
        $mismatches = [];

        if (Schema::hasColumn($table, 'business_id')) {
            if (empty($row->business_id)) {
                $safePayload['business_id'] = (int) $master->business_id;
            } elseif ((int) $row->business_id !== (int) $master->business_id) {
                $mismatches['business_id'] = [(int) $row->business_id, (int) $master->business_id];
            }
        }

        if (Schema::hasColumn($table, 'shift_id')) {
            if (empty($row->shift_id)) {
                $safePayload['shift_id'] = (int) $master->shift_id;
            } elseif ((int) $row->shift_id !== (int) $master->shift_id) {
                $mismatches['shift_id'] = [(int) $row->shift_id, (int) $master->shift_id];
            }
        }

        $operatorColumn = $config['operator'];
        if ($operatorColumn && Schema::hasColumn($table, $operatorColumn)) {
            if (empty($row->{$operatorColumn})) {
                $safePayload[$operatorColumn] = (int) $master->pump_operator_id;
            } elseif ((int) $row->{$operatorColumn} !== (int) $master->pump_operator_id) {
                $mismatches[$operatorColumn] = [(int) $row->{$operatorColumn}, (int) $master->pump_operator_id];
            }
        }

        if (! empty($mismatches)) {
            $tableSummary['issues']++;
            $this->summary['scope_mismatches']++;
            $this->issue(
                $table,
                (int) $row->id,
                (int) $master->id,
                (int) $master->shift_id,
                'supporting_scope_mismatch',
                'critical',
                'Supporting row has a conflicting non-zero business/operator/Shift value. It was not overwritten automatically.',
                ['row' => (array) $row, 'master' => (array) $master, 'mismatches' => $mismatches]
            );
        }

        $this->validateLinkedAmounts($table, $config, $row, $master, $tableSummary);

        if (! empty($safePayload)) {
            $tableSummary['safe_repairs']++;
            $this->summary['safe_repairs_found']++;
            $applied = false;
            if ($this->apply) {
                $applied = $this->applySafeUpdate($table, (int) $row->id, $safePayload);
                if ($applied) {
                    $tableSummary['applied']++;
                    $this->summary['repairs_applied']++;
                }
            }

            $this->action(
                $table,
                (int) $row->id,
                (int) $master->id,
                (int) $master->shift_id,
                'fill_missing_scope_from_master',
                'warning',
                true,
                $applied,
                'Missing supporting scope fields can be filled from the linked authoritative payment.',
                (array) $row,
                array_merge((array) $row, $safePayload)
            );
        }
    }

    /**
     * Amounts are never repaired automatically. A mismatch is financial
     * evidence and must remain visible until reviewed. Comparison is performed
     * only for a one-to-one supporting row; duplicates are reported separately.
     */
    private function validateLinkedAmounts(
        string $table,
        array $config,
        object $row,
        object $master,
        array &$tableSummary
    ): void {
        $amountMap = $config['amount'] ?? null;
        if (! is_array($amountMap) || empty($amountMap)) {
            return;
        }

        if (Schema::hasColumn($table, 'pump_payment_id')) {
            $linkedCount = DB::table($table)
                ->where('pump_payment_id', $master->id)
                ->count();
            if ($linkedCount !== 1) {
                return;
            }
        }

        $masterGross = property_exists($master, 'gross_amount') && $master->gross_amount !== null
            ? (float) $master->gross_amount
            : (float) ($master->payment_amount ?? 0);
        $masterDiscount = property_exists($master, 'discount_amount') && $master->discount_amount !== null
            ? (float) $master->discount_amount
            : 0.0;
        $masterNet = property_exists($master, 'net_amount') && $master->net_amount !== null
            ? (float) $master->net_amount
            : ($masterGross - $masterDiscount);

        $expected = [
            'gross' => $masterGross,
            'discount' => $masterDiscount,
            'net' => $masterNet,
        ];
        $mismatches = [];

        foreach ($amountMap as $kind => $column) {
            if (! Schema::hasColumn($table, $column) || ! isset($expected[$kind])) {
                continue;
            }

            $detailAmount = (float) ($row->{$column} ?? 0);
            if (abs($detailAmount - $expected[$kind]) > 0.01) {
                $mismatches[$column] = [
                    'detail' => round($detailAmount, 4),
                    'master' => round($expected[$kind], 4),
                    'kind' => $kind,
                ];
            }
        }

        if (empty($mismatches)) {
            return;
        }

        $tableSummary['issues']++;
        $this->summary['amount_mismatches']++;
        $this->issue(
            $table,
            (int) $row->id,
            (int) $master->id,
            ! empty($master->shift_id) ? (int) $master->shift_id : null,
            'supporting_amount_mismatch',
            'critical',
            'Supporting payment amount differs from the authoritative Pump Operator Payment. No amount was changed automatically.',
            ['row' => (array) $row, 'master' => (array) $master, 'mismatches' => $mismatches]
        );
    }

    private function repairMissingMasterShiftsFromLinkedDetails(): void
    {
        $query = DB::table('pump_operator_payments')
            ->where(function ($q) {
                $q->whereNull('shift_id')->orWhere('shift_id', 0);
            })
            ->whereIn(DB::raw('LOWER(payment_type)'), $this->allPaymentTypeAliases());
        $this->applyMasterFilters($query);

        foreach ($query->orderBy('id')->get() as $master) {
            $shiftCandidates = [];
            $operatorCandidates = [];

            foreach (array_keys(self::TABLES) as $table) {
                if (! Schema::hasTable($table)
                    || ! Schema::hasColumn($table, 'pump_payment_id')
                    || ! Schema::hasColumn($table, 'shift_id')) {
                    continue;
                }

                $rows = DB::table($table)
                    ->where('pump_payment_id', $master->id)
                    ->get();
                foreach ($rows as $row) {
                    if (! empty($row->shift_id)) {
                        $shiftCandidates[] = (int) $row->shift_id;
                    }
                    $operatorColumn = self::TABLES[$table]['operator'];
                    if ($operatorColumn && isset($row->{$operatorColumn}) && ! empty($row->{$operatorColumn})) {
                        $operatorCandidates[] = (int) $row->{$operatorColumn};
                    }
                }
            }

            $shiftCandidates = array_values(array_unique(array_filter($shiftCandidates)));
            $operatorCandidates = array_values(array_unique(array_filter($operatorCandidates)));

            if (count($shiftCandidates) === 1
                && (empty($operatorCandidates)
                    || (count($operatorCandidates) === 1 && $operatorCandidates[0] === (int) $master->pump_operator_id))) {
                $payload = ['shift_id' => $shiftCandidates[0]];
                $this->summary['safe_repairs_found']++;
                $applied = false;
                if ($this->apply) {
                    $applied = DB::table('pump_operator_payments')
                        ->where('id', $master->id)
                        ->where(function ($q) {
                            $q->whereNull('shift_id')->orWhere('shift_id', 0);
                        })
                        ->update($payload) === 1;
                    if ($applied) {
                        $this->summary['repairs_applied']++;
                    }
                }

                $this->action(
                    'pump_operator_payments',
                    (int) $master->id,
                    (int) $master->id,
                    $shiftCandidates[0],
                    'fill_master_shift_from_unanimous_details',
                    'warning',
                    true,
                    $applied,
                    'All linked supporting rows agree on one Shift ID.',
                    (array) $master,
                    array_merge((array) $master, $payload)
                );
            } elseif (count($shiftCandidates) > 1) {
                $this->issue(
                    'pump_operator_payments',
                    (int) $master->id,
                    (int) $master->id,
                    null,
                    'conflicting_detail_shift_candidates',
                    'critical',
                    'Linked supporting rows disagree on the Shift ID for this master payment.',
                    ['shift_candidates' => $shiftCandidates]
                );
            }
        }
    }

    private function scanDuplicateSourceIdentities(): void
    {
        if (! Schema::hasColumn('pump_operator_payments', 'source_type')
            || ! Schema::hasColumn('pump_operator_payments', 'source_id')) {
            return;
        }

        $query = DB::table('pump_operator_payments')
            ->whereNotNull('source_type')->where('source_type', '<>', '')
            ->whereNotNull('source_id')->where('source_id', '>', 0)
            ->select('business_id', 'source_type', 'source_id', DB::raw('COUNT(*) as row_count'), DB::raw('GROUP_CONCAT(id ORDER BY id) as payment_ids'))
            ->groupBy('business_id', 'source_type', 'source_id')
            ->havingRaw('COUNT(*) > 1');

        if ($this->filters['business_id']) {
            $query->where('business_id', $this->filters['business_id']);
        }

        foreach ($query->get() as $row) {
            $this->issue(
                'pump_operator_payments',
                null,
                null,
                null,
                'duplicate_master_source_identity',
                'critical',
                'More than one authoritative payment uses the same source_type/source_id identity.',
                (array) $row
            );
        }
    }

    private function applyMasterFilters(Builder $query): void
    {
        if ($this->filters['business_id']) {
            $query->where('business_id', $this->filters['business_id']);
        }
        if ($this->filters['shift_id']) {
            $query->where('shift_id', $this->filters['shift_id']);
        }
        if ($this->filters['settlement_no'] !== null) {
            $query->where('settlement_no', $this->filters['settlement_no']);
        }
    }

    private function applySupportingFilters(Builder $query, string $table, array $config): void
    {
        if ($this->filters['business_id'] && Schema::hasColumn($table, 'business_id')) {
            $query->where($table . '.business_id', $this->filters['business_id']);
        }
        if ($this->filters['shift_id'] && Schema::hasColumn($table, 'shift_id')) {
            $query->where($table . '.shift_id', $this->filters['shift_id']);
        }
        if ($this->filters['settlement_no'] !== null && Schema::hasColumn($table, 'settlement_no')) {
            $query->where($table . '.settlement_no', $this->filters['settlement_no']);
        }
    }

    private function applySafeUpdate(string $table, int $rowId, array $payload): bool
    {
        if (empty($payload)) {
            return false;
        }

        return DB::transaction(function () use ($table, $rowId, $payload) {
            return DB::table($table)->where('id', $rowId)->update($payload) === 1;
        });
    }

    private function issue(
        string $table,
        ?int $rowId,
        ?int $pumpPaymentId,
        ?int $shiftId,
        string $type,
        string $severity,
        string $message,
        array $context
    ): void {
        $this->summary['issues_found']++;
        if ($severity === 'critical') {
            $this->summary['critical_issues']++;
        } else {
            $this->summary['warnings']++;
        }

        $this->action(
            $table,
            $rowId,
            $pumpPaymentId,
            $shiftId,
            $type,
            $severity,
            false,
            false,
            $message,
            $context,
            []
        );
    }

    private function action(
        string $table,
        ?int $rowId,
        ?int $pumpPaymentId,
        ?int $shiftId,
        string $type,
        string $severity,
        bool $safe,
        bool $applied,
        string $message,
        array $before,
        array $after
    ): void {
        if (! $this->runId || ! Schema::hasTable('petro_pd_payment_integrity_repair_actions')) {
            return;
        }

        DB::table('petro_pd_payment_integrity_repair_actions')->insert([
            'repair_run_id' => $this->runId,
            'business_id' => $before['business_id'] ?? $after['business_id'] ?? $this->filters['business_id'],
            'table_name' => $table,
            'row_id' => $rowId,
            'pump_payment_id' => $pumpPaymentId,
            'shift_id' => $shiftId,
            'action_type' => $type,
            'severity' => $severity,
            'is_safe_repair' => $safe ? 1 : 0,
            'was_applied' => $applied ? 1 : 0,
            'message' => $message,
            'before_json' => empty($before) ? null : json_encode($before, JSON_UNESCAPED_SLASHES),
            'after_json' => empty($after) ? null : json_encode($after, JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function startRun(array $options): void
    {
        if (! Schema::hasTable('petro_pd_payment_integrity_repair_runs')) {
            return;
        }

        $this->runId = DB::table('petro_pd_payment_integrity_repair_runs')->insertGetId([
            'business_id' => $this->filters['business_id'],
            'tenant_reference' => $options['tenant_reference'] ?? null,
            'apply_changes' => $this->apply ? 1 : 0,
            'status' => 'running',
            'options_json' => json_encode($options, JSON_UNESCAPED_SLASHES),
            'started_at' => now(),
            'created_by' => Auth::check() ? Auth::id() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function finishRun(string $status, ?string $error = null): void
    {
        if (! $this->runId || ! Schema::hasTable('petro_pd_payment_integrity_repair_runs')) {
            return;
        }

        DB::table('petro_pd_payment_integrity_repair_runs')
            ->where('id', $this->runId)
            ->update([
                'status' => $status,
                'scanned_rows' => $this->summary['scanned_rows'] ?? 0,
                'issues_found' => $this->summary['issues_found'] ?? 0,
                'safe_repairs_found' => $this->summary['safe_repairs_found'] ?? 0,
                'repairs_applied' => $this->summary['repairs_applied'] ?? 0,
                'summary_json' => json_encode($this->summary, JSON_UNESCAPED_SLASHES),
                'error_message' => $error,
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function rowShiftId(string $table, object $row): ?int
    {
        return Schema::hasColumn($table, 'shift_id') && ! empty($row->shift_id)
            ? (int) $row->shift_id
            : null;
    }

    private function allPaymentTypeAliases(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::MASTER_TYPES))));
    }
}
