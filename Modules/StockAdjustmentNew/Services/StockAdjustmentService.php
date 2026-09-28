<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockAdjustmentNew\Entities\StockAdjustment;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentAudit;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentMovement;

class StockAdjustmentService
{
    public function __construct(
        private AdjustmentNumberService $numbers,
        private StockAdjustmentSettingsService $settings,
        private HostStockPostingService $hostPosting
    ) {}

    public function create(array $data, array $lines, ?int $businessId, ?int $userId): StockAdjustment
    {
        $settings = $this->settings->values($businessId);
        $autoSubmit = (bool) ($settings['auto_submit'] ?? false);
        $requireApproval = (bool) ($settings['require_approval'] ?? true);
        $documentDirection = $this->documentDirection($lines);

        $adjustment = DB::transaction(function () use (
            $data,
            $lines,
            $businessId,
            $userId,
            $autoSubmit,
            $requireApproval,
            $settings,
            $documentDirection
        ) {
            $status = 'draft';
            $approvalData = [];

            if ($autoSubmit) {
                if ($requireApproval) {
                    $status = 'submitted';
                } else {
                    $status = 'approved';
                    $approvalData = [
                        'approved_by' => $userId,
                        'approved_at' => now(),
                        'approval_remarks' => 'Automatically approved by Stock Adjustment settings.',
                    ];
                }
            }

            $adjustment = StockAdjustment::create([
                'business_id' => $businessId,
                'location_id' => $data['location_id'] ?? null,
                'store_id' => $data['store_id'] ?? null,
                'adjustment_no' => $this->numbers->next($businessId),
                'adjustment_date' => $data['adjustment_date'] ?? now()->toDateString(),
                'adjustment_type' => $data['adjustment_type'] ?? ($settings['default_adjustment_type'] ?? 'quantity'),
                'stock_adjustment_type' => $documentDirection,
                'reason_id' => $data['reason_id'] ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ] + $approvalData);

            $this->syncLines($adjustment, $lines);
            $this->audit($adjustment, 'created', $userId, ['lines' => count($lines)]);

            if ($status === 'submitted') {
                $this->audit($adjustment, 'auto_submitted', $userId);
            } elseif ($status === 'approved') {
                $this->audit($adjustment, 'auto_approved', $userId);
            }

            return $adjustment->refresh();
        });

        if (
            $adjustment->status === 'approved'
            && (bool) ($settings['auto_post_after_approval'] ?? false)
        ) {
            $this->post($adjustment, $userId);
        }

        return $adjustment->refresh();
    }

    public function submit(StockAdjustment $adjustment, ?int $userId): void
    {
        if ($adjustment->status !== 'draft') {
            throw new \RuntimeException('Only draft stock adjustments can be submitted.');
        }

        $settings = $this->settings->values($adjustment->business_id ? (int) $adjustment->business_id : null);
        if ((bool) ($settings['require_approval'] ?? true)) {
            $this->transition($adjustment, 'submitted', $userId, 'submitted');
            return;
        }

        $this->approve($adjustment, $userId, 'Approval was not required by Stock Adjustment settings.');
    }

    public function approve(StockAdjustment $adjustment, ?int $userId, ?string $remarks = null): void
    {
        if (! in_array($adjustment->status, ['draft', 'submitted'], true)) {
            throw new \RuntimeException('Only draft or submitted stock adjustments can be approved.');
        }

        $adjustment->forceFill([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'approval_remarks' => $remarks,
        ])->save();
        $this->audit($adjustment, 'approved', $userId, ['remarks' => $remarks]);

        $settings = $this->settings->values($adjustment->business_id ? (int) $adjustment->business_id : null);
        if ((bool) ($settings['auto_post_after_approval'] ?? false)) {
            $this->post($adjustment, $userId);
        }
    }

    public function reject(StockAdjustment $adjustment, ?int $userId, ?string $remarks = null): void
    {
        if (! in_array($adjustment->status, ['submitted', 'approved'], true)) {
            throw new \RuntimeException('Only submitted or approved stock adjustments can be rejected.');
        }

        $adjustment->forceFill(['status' => 'rejected', 'approval_remarks' => $remarks])->save();
        $this->audit($adjustment, 'rejected', $userId, ['remarks' => $remarks]);
    }

    public function post(StockAdjustment $adjustment, ?int $userId): void
    {
        DB::transaction(function () use ($adjustment, $userId): void {
            /** @var StockAdjustment $locked */
            $locked = StockAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'posted') {
                throw new \RuntimeException('This stock adjustment has already been posted.');
            }

            if ($locked->status !== 'approved') {
                throw new \RuntimeException('Only approved stock adjustments can be posted.');
            }

            $locked->load('lines');
            $settings = $this->settings->values($locked->business_id ? (int) $locked->business_id : null);
            $posting = $this->hostPosting->post($locked, $userId, $settings);

            foreach ($locked->lines as $line) {
                if (abs((float) $line->adjustment_qty) <= 0.0000001) {
                    continue;
                }

                StockAdjustmentMovement::query()->firstOrCreate(
                    [
                        'adjustment_id' => $locked->id,
                        'adjustment_line_id' => $line->id,
                    ],
                    [
                        'business_id' => $locked->business_id,
                        'location_id' => $locked->location_id,
                        'store_id' => $locked->store_id,
                        'product_id' => $line->product_id,
                        'variation_id' => $line->variation_id,
                        'batch_no' => $line->batch_no,
                        'movement_type' => in_array($line->stock_adjustment_type, ['increase', 'decrease'], true)
                            ? $line->stock_adjustment_type
                            : ((float) $line->adjustment_qty >= 0 ? 'increase' : 'decrease'),
                        'qty_change' => $line->adjustment_qty,
                        'cost_change' => $line->cost_amount,
                        'movement_date' => now(),
                        'created_by' => $userId,
                    ]
                );
            }

            $locked->forceFill([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
                'host_transaction_id' => $posting['host_transaction_id'] ?? null,
                'posting_summary' => $posting,
            ])->save();

            $this->audit($locked, 'posted', $userId, $posting + [
                'lines' => $locked->lines->count(),
            ]);
        });

        $adjustment->refresh();
    }

    private function syncLines(StockAdjustment $adjustment, array $lines): void
    {
        $totalQty = 0;
        $totalCost = 0;

        foreach ($lines as $line) {
            $system = (float) ($line['system_qty'] ?? 0);
            $counted = (float) ($line['counted_qty'] ?? 0);
            $diff = $counted - $system;
            $unitCost = (float) ($line['unit_cost'] ?? 0);
            $cost = $diff * $unitCost;
            $direction = strtolower((string) ($line['stock_adjustment_type'] ?? ''));

            if (! in_array($direction, ['increase', 'decrease'], true)) {
                throw new \RuntimeException('Every stock adjustment line must be marked Increase or Decrease.');
            }
            if (($direction === 'increase' && $diff <= 0) || ($direction === 'decrease' && $diff >= 0)) {
                throw new \RuntimeException('The counted quantity does not match the selected Increase/Decrease type.');
            }

            $adjustment->lines()->create([
                'product_id' => $line['product_id'] ?? null,
                'variation_id' => $line['variation_id'] ?? null,
                'product_name' => $line['product_name'] ?? null,
                'sku' => $line['sku'] ?? null,
                'batch_no' => $line['batch_no'] ?? null,
                'expiry_date' => $line['expiry_date'] ?? null,
                'system_qty' => $system,
                'counted_qty' => $counted,
                'adjustment_qty' => $diff,
                'stock_adjustment_type' => $direction,
                'unit_cost' => $unitCost,
                'cost_amount' => $cost,
                'line_notes' => $line['line_notes'] ?? null,
            ]);

            $totalQty += $diff;
            $totalCost += $cost;
        }

        $adjustment->forceFill([
            'total_qty' => $totalQty,
            'total_cost_amount' => $totalCost,
        ])->save();
    }

    /** @param array<int, array<string, mixed>> $lines */
    private function documentDirection(array $lines): string
    {
        $directions = [];
        foreach ($lines as $line) {
            $direction = strtolower((string) ($line['stock_adjustment_type'] ?? ''));
            if (! in_array($direction, ['increase', 'decrease'], true)) {
                throw new \RuntimeException('Every stock adjustment line must be marked Increase or Decrease.');
            }
            $directions[$direction] = true;
        }

        if (isset($directions['increase'], $directions['decrease'])) {
            return 'mixed';
        }

        return isset($directions['decrease']) ? 'decrease' : 'increase';
    }

    private function transition(StockAdjustment $adjustment, string $status, ?int $userId, string $event): void
    {
        $adjustment->forceFill(['status' => $status])->save();
        $this->audit($adjustment, $event, $userId);
    }

    private function audit(StockAdjustment $adjustment, string $event, ?int $userId, array $payload = []): void
    {
        StockAdjustmentAudit::create([
            'business_id' => $adjustment->business_id,
            'adjustment_id' => $adjustment->id,
            'event' => $event,
            'payload' => $payload,
            'created_by' => $userId,
        ]);
    }
}
