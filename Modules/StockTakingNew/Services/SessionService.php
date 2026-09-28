<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Entities\StockTakeTemplate;

class SessionService
{
    public function __construct(
        private StockTakingNumberService $numbers,
        private InventoryBridgeService $inventory,
        private AuditService $audit
    ) {}

    public function create(int $businessId, ?int $userId, array $data): StockTakeSession
    {
        return DB::transaction(function () use ($businessId, $userId, $data): StockTakeSession {
            $session = StockTakeSession::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'],
                'store_id' => $data['store_id'] ?? null,
                'template_id' => $data['template_id'] ?? null,
                'stock_take_no' => $this->numbers->next($businessId, (int) $data['location_id']),
                'title' => $data['title'],
                'count_date' => $data['count_date'],
                'cutoff_at' => $data['cutoff_at'] ?? now(),
                'count_method' => $data['count_method'],
                'count_mode' => $data['count_mode'],
                'scope_json' => $data['scope'] ?? [],
                'status' => 'draft',
                'freeze_stock' => ! empty($data['freeze_stock']),
                'require_recount' => ! empty($data['require_recount']),
                'variance_qty_threshold' => (float) ($data['variance_qty_threshold'] ?? 0),
                'variance_value_threshold' => (float) ($data['variance_value_threshold'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);
            $this->audit->log($businessId, $session->id, 'session_created', 'session', $session->id, [], [
                'stock_take_no' => $session->stock_take_no,
            ], [], $userId);

            return $session;
        });
    }

    public function prepare(StockTakeSession $session, ?int $userId = null): int
    {
        if (! in_array($session->status, ['draft', 'prepared'], true)) {
            throw new \RuntimeException('Only draft or prepared sessions can refresh their product snapshot.');
        }

        return DB::transaction(function () use ($session, $userId): int {
            $scope = (array) $session->scope_json;
            if ($session->template_id) {
                $template = StockTakeTemplate::where('business_id', $session->business_id)
                    ->with('lines')->find($session->template_id);
                if ($template) {
                    $scope = array_merge((array) $template->scope_json, $scope);
                    $templateProductIds = $template->lines->pluck('product_id')->filter()->map(fn ($id) => (int) $id)->all();
                    if ($templateProductIds) {
                        $scope['product_ids'] = array_values(array_unique(array_merge(
                            (array) ($scope['product_ids'] ?? []),
                            $templateProductIds
                        )));
                    }
                }
            }

            $products = $this->inventory->snapshot(
                (int) $session->business_id,
                (int) $session->location_id,
                $session->store_id ? (int) $session->store_id : null,
                $scope
            );

            $session->lines()->delete();
            $systemTotal = 0.0;
            foreach ($products as $row) {
                $session->lines()->create(array_merge($row, [
                    'business_id' => $session->business_id,
                    'location_id' => $session->location_id,
                    'store_id' => $session->store_id,
                    'system_qty' => $row['system_qty'],
                    'final_count_qty' => null,
                    'variance_qty' => 0,
                    'variance_value' => 0,
                    'count_status' => 'pending',
                    'is_frozen' => $session->freeze_stock,
                ]));
                $systemTotal += (float) $row['system_qty'];
            }

            $actorId = $userId ?? auth()->id();
            $session->update([
                'scope_json' => $scope,
                'status' => 'prepared',
                'line_count' => count($products),
                'counted_line_count' => 0,
                'system_qty_total' => $systemTotal,
                'counted_qty_total' => 0,
                'variance_qty_total' => 0,
                'variance_value_total' => 0,
                'prepared_by' => $actorId,
                'prepared_at' => now(),
            ]);
            $this->audit->log($session->business_id, $session->id, 'snapshot_prepared', 'session', $session->id, [], [
                'line_count' => count($products),
                'system_qty_total' => $systemTotal,
            ], [], $actorId);

            return count($products);
        });
    }

    public function start(StockTakeSession $session): void
    {
        if (! in_array($session->status, ['prepared', 'counting'], true)) {
            throw new \RuntimeException('Prepare the stock snapshot before starting the count.');
        }
        if ((int) $session->line_count === 0) {
            throw new \RuntimeException('The stock snapshot contains no products. Review the location, store or template scope.');
        }

        $oldStatus = $session->status;
        $session->update([
            'status' => 'counting',
            'started_by' => auth()->id(),
            'started_at' => $session->started_at ?: now(),
        ]);
        $this->audit->log($session->business_id, $session->id, 'counting_started', 'session', $session->id,
            ['status' => $oldStatus], ['status' => 'counting']);
    }

    public function recalculate(StockTakeSession $session): void
    {
        $totals = $session->lines()->selectRaw(
            'COUNT(*) line_count, '
            . 'SUM(CASE WHEN is_counted = 1 THEN 1 ELSE 0 END) counted_line_count, '
            . 'COALESCE(SUM(system_qty), 0) system_qty_total, '
            . 'COALESCE(SUM(final_count_qty), 0) counted_qty_total, '
            . 'COALESCE(SUM(variance_qty), 0) variance_qty_total, '
            . 'COALESCE(SUM(variance_value), 0) variance_value_total'
        )->first();

        $session->update([
            'line_count' => (int) $totals->line_count,
            'counted_line_count' => (int) $totals->counted_line_count,
            'system_qty_total' => (float) $totals->system_qty_total,
            'counted_qty_total' => (float) $totals->counted_qty_total,
            'variance_qty_total' => (float) $totals->variance_qty_total,
            'variance_value_total' => (float) $totals->variance_value_total,
        ]);
        $session->refresh();
    }
}
