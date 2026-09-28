<?php

namespace Modules\RiceMill\Services;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side stock reconstruction for by-products.
 *
 * Production creates one movement per by-product type / production batch.
 * Outgoing sales, free issues, disposals and manual reductions historically
 * did not carry a source-batch column, so current source balances are rebuilt
 * FIFO from the immutable movement ledger. This keeps old tenant data usable
 * without changing or fabricating historical rows.
 */
class ByproductStockService
{
    public function __construct(private StandardListService $lists)
    {
    }

    /**
     * @return array{summary:Collection,rows:LengthAwarePaginator}
     */
    public function currentStock(int $businessId, Request $request): array
    {
        $movements = DB::table('rcm_byproduct_movements')
            ->where('business_id', $businessId)
            ->orderBy('id')
            ->get([
                'id','byproduct_type','movement_date','movement_type','quantity',
                'signed_quantity','reference_type','reference_id','note'
            ]);

        $summary = [];
        $sources = [];
        $sourceQueues = [];

        foreach ($movements as $movement) {
            $type = (string) $movement->byproduct_type;
            $signed = (float) $movement->signed_quantity;
            $qty = abs((float) $movement->quantity);

            if (! isset($summary[$type])) {
                $summary[$type] = [
                    'byproduct_type' => $type,
                    'balance_qty' => 0.0,
                    'produced_qty' => 0.0,
                    'sale_qty' => 0.0,
                    'free_qty' => 0.0,
                    'dispose_qty' => 0.0,
                    'other_out_qty' => 0.0,
                ];
            }

            $summary[$type]['balance_qty'] += $signed;
            if ($signed > 0) {
                $summary[$type]['produced_qty'] += $signed;

                $key = 'm' . (int) $movement->id;
                $sources[$key] = [
                    'source_movement_id' => (int) $movement->id,
                    'byproduct_type' => $type,
                    'movement_date' => (string) $movement->movement_date,
                    'production_batch_id' => $this->productionBatchId($movement),
                    'production_batch_no' => null,
                    'paddy_lots' => '',
                    'paddy_names' => '',
                    'source_qty' => $signed,
                    'sale_qty' => 0.0,
                    'free_qty' => 0.0,
                    'dispose_qty' => 0.0,
                    'other_out_qty' => 0.0,
                    'balance_qty' => $signed,
                    'source_label' => $this->isProduction($movement) ? 'Production' : ucwords(str_replace('_', ' ', (string) $movement->movement_type)),
                ];
                $sourceQueues[$type][] = $key;
                continue;
            }

            if ($signed >= 0) {
                continue;
            }

            $category = $this->outCategory((string) $movement->movement_type);
            $remaining = abs($signed ?: $qty);
            $summary[$type][$category] += $remaining;

            foreach ($sourceQueues[$type] ?? [] as $sourceKey) {
                if ($remaining <= 0.0000001) {
                    break;
                }
                $available = max(0.0, (float) $sources[$sourceKey]['balance_qty']);
                if ($available <= 0.0000001) {
                    continue;
                }
                $used = min($available, $remaining);
                $sources[$sourceKey]['balance_qty'] -= $used;
                $sources[$sourceKey][$category] += $used;
                $remaining -= $used;
            }
        }

        $batchIds = array_values(array_unique(array_filter(array_map(
            static fn (array $row) => $row['production_batch_id'],
            $sources
        ))));

        $batchMap = $this->batchSourceMap($businessId, $batchIds);
        foreach ($sources as &$row) {
            $batchId = $row['production_batch_id'];
            if ($batchId && isset($batchMap[$batchId])) {
                $row['production_batch_no'] = $batchMap[$batchId]['batch_no'];
                $row['paddy_lots'] = $batchMap[$batchId]['paddy_lots'];
                $row['paddy_names'] = $batchMap[$batchId]['paddy_names'];
            }
        }
        unset($row);

        // Current stock page: only sources that still carry stock. A by-product
        // type whose balance is zero remains visible in the summary/ledger area.
        $rows = array_values(array_filter($sources, static fn (array $row) => (float) $row['balance_qty'] > 0.0000001));
        $rows = $this->filterRows($rows, $businessId, $request);

        usort($rows, static function (array $a, array $b): int {
            $byType = strcasecmp($a['byproduct_type'], $b['byproduct_type']);
            if ($byType !== 0) {
                return $byType;
            }
            return $b['source_movement_id'] <=> $a['source_movement_id'];
        });

        $perPage = $this->lists->perPage($request, 25);
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * $perPage;
        $pageRows = array_slice($rows, $offset, $perPage);
        $paginator = new LengthAwarePaginator(
            $pageRows,
            count($rows),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $summaryRows = collect(array_values($summary))
            ->sortBy('byproduct_type', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return ['summary' => $summaryRows, 'rows' => $paginator];
    }

    /** @return array<int,array{batch_no:string,paddy_lots:string,paddy_names:string}> */
    public function batchSourceMap(int $businessId, array $batchIds): array
    {
        if (! $batchIds) {
            return [];
        }

        $batches = DB::table('rcm_production_batches')
            ->where('business_id', $businessId)
            ->whereIn('id', $batchIds)
            ->pluck('batch_no', 'id');

        $inputs = DB::table('rcm_production_inputs as pi')
            ->join('rcm_paddy_lots as l', function ($join) use ($businessId) {
                $join->on('l.id', '=', 'pi.paddy_lot_id')
                    ->where('l.business_id', '=', $businessId);
            })
            ->leftJoin('rcm_paddy_varieties as pv', function ($join) use ($businessId) {
                $join->on('pv.id', '=', 'l.paddy_variety_id')
                    ->where('pv.business_id', '=', $businessId);
            })
            ->where('pi.business_id', $businessId)
            ->whereIn('pi.production_batch_id', $batchIds)
            ->orderBy('pi.id')
            ->get([
                'pi.production_batch_id','l.lot_no','pv.code as paddy_code','pv.name as paddy_name'
            ]);

        $lots = [];
        $names = [];
        foreach ($inputs as $input) {
            $batchId = (int) $input->production_batch_id;
            if ($input->lot_no) {
                $lots[$batchId][(string) $input->lot_no] = true;
            }
            $name = trim((string) ($input->paddy_name ?? ''));
            $code = trim((string) ($input->paddy_code ?? ''));
            $label = $name !== '' ? $name : $code;
            if ($label !== '' && $code !== '' && $name !== '' && stripos($name, $code) === false) {
                $label .= ' (' . $code . ')';
            }
            if ($label !== '') {
                $names[$batchId][$label] = true;
            }
        }

        $result = [];
        foreach ($batchIds as $batchId) {
            $batchId = (int) $batchId;
            $result[$batchId] = [
                'batch_no' => (string) ($batches[$batchId] ?? ('#' . $batchId)),
                'paddy_lots' => implode(', ', array_keys($lots[$batchId] ?? [])),
                'paddy_names' => implode(', ', array_keys($names[$batchId] ?? [])),
            ];
        }
        return $result;
    }

    /**
     * Reconstruct the production source(s) consumed by each ledger movement.
     * This gives Sale / Free / Dispose rows meaningful Paddy Lot and Production
     * Batch traceability even for historical databases that only stored the
     * by-product type on outbound movements.
     *
     * @return array<int,array{production_batches:string,paddy_lots:string,paddy_names:string}>
     */
    public function ledgerSourceAllocations(int $businessId, string $type): array
    {
        $movements = DB::table('rcm_byproduct_movements')
            ->where('business_id', $businessId)
            ->where('byproduct_type', $type)
            ->orderBy('id')
            ->get(['id','movement_type','signed_quantity','reference_type','reference_id']);

        $sources = [];
        $queue = [];
        $movementBatchIds = [];
        $allBatchIds = [];

        foreach ($movements as $movement) {
            $signed = (float) $movement->signed_quantity;
            if ($signed > 0) {
                $batchId = $this->productionBatchId($movement);
                $sourceKey = 'm' . (int) $movement->id;
                $sources[$sourceKey] = [
                    'remaining' => $signed,
                    'batch_id' => $batchId,
                ];
                $queue[] = $sourceKey;
                if ($batchId) {
                    $movementBatchIds[(int) $movement->id][$batchId] = true;
                    $allBatchIds[$batchId] = true;
                }
                continue;
            }

            if ($signed >= 0) {
                continue;
            }

            $remaining = abs($signed);
            foreach ($queue as $sourceKey) {
                if ($remaining <= 0.0000001) {
                    break;
                }
                $available = max(0.0, (float) $sources[$sourceKey]['remaining']);
                if ($available <= 0.0000001) {
                    continue;
                }
                $used = min($available, $remaining);
                $sources[$sourceKey]['remaining'] -= $used;
                $remaining -= $used;
                $batchId = $sources[$sourceKey]['batch_id'];
                if ($batchId) {
                    $movementBatchIds[(int) $movement->id][$batchId] = true;
                    $allBatchIds[$batchId] = true;
                }
            }
        }

        $batchMap = $this->batchSourceMap($businessId, array_keys($allBatchIds));
        $result = [];
        foreach ($movements as $movement) {
            $batchNos = [];
            $lotNos = [];
            $paddyNames = [];
            foreach (array_keys($movementBatchIds[(int) $movement->id] ?? []) as $batchId) {
                if (! isset($batchMap[$batchId])) {
                    continue;
                }
                $batchNos[$batchMap[$batchId]['batch_no']] = true;
                foreach (array_filter(array_map('trim', explode(',', $batchMap[$batchId]['paddy_lots']))) as $lot) {
                    $lotNos[$lot] = true;
                }
                foreach (array_filter(array_map('trim', explode(',', $batchMap[$batchId]['paddy_names']))) as $name) {
                    $paddyNames[$name] = true;
                }
            }
            $result[(int) $movement->id] = [
                'production_batches' => implode(', ', array_keys($batchNos)),
                'paddy_lots' => implode(', ', array_keys($lotNos)),
                'paddy_names' => implode(', ', array_keys($paddyNames)),
            ];
        }
        return $result;
    }

    public function movementPresentation(string $movementType): array
    {
        $category = $this->outCategory($movementType);
        return [
            'sale' => $category === 'sale_qty',
            'free' => $category === 'free_qty',
            'dispose' => $category === 'dispose_qty',
            'other_out' => $category === 'other_out_qty',
        ];
    }

    private function filterRows(array $rows, int $businessId, Request $request): array
    {
        $state = $this->lists->dateState($request, $businessId);
        $term = mb_strtolower($this->lists->searchTerm($request));

        return array_values(array_filter($rows, static function (array $row) use ($state, $term): bool {
            if ($state['range'] !== 'all') {
                $date = substr((string) $row['movement_date'], 0, 10);
                if ($state['from'] !== '' && $date < $state['from']) {
                    return false;
                }
                if ($state['to'] !== '' && $date > $state['to']) {
                    return false;
                }
            }

            if ($term !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $row['byproduct_type'],
                    $row['production_batch_no'] ?? '',
                    $row['paddy_lots'] ?? '',
                    $row['paddy_names'] ?? '',
                    $row['source_label'] ?? '',
                ]));
                if (mb_strpos($haystack, $term) === false) {
                    return false;
                }
            }
            return true;
        }));
    }

    private function isProduction(object $movement): bool
    {
        return (string) $movement->movement_type === 'production'
            || (string) $movement->reference_type === 'production_batch';
    }

    private function productionBatchId(object $movement): ?int
    {
        if ((string) $movement->reference_type !== 'production_batch' || ! $movement->reference_id) {
            return null;
        }
        return (int) $movement->reference_id;
    }

    private function outCategory(string $movementType): string
    {
        $type = strtolower(trim($movementType));
        if (in_array($type, ['sale','sales','sold','dispatch','byproduct_sale'], true)) {
            return 'sale_qty';
        }
        if (in_array($type, ['free','free_issue','free_qty','complimentary'], true)) {
            return 'free_qty';
        }
        if (in_array($type, ['dispose','disposed','disposal','waste'], true)) {
            return 'dispose_qty';
        }
        return 'other_out_qty';
    }
}
