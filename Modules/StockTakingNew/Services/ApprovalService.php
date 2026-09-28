<?php

namespace Modules\StockTakingNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\StockTakingNew\Entities\StockTakeApproval;
use Modules\StockTakingNew\Entities\StockTakeInventoryMovement;
use Modules\StockTakingNew\Entities\StockTakeSession;

class ApprovalService
{
    public function __construct(
        private InventoryBridgeService $inventory,
        private SettingsService $settings,
        private AuditService $audit
    ) {}

    public function approve(StockTakeSession $session, ?string $remarks = null): void
    {
        DB::transaction(function () use ($session, $remarks): void {
            $locked = StockTakeSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['submitted', 'under_review'], true)) {
                throw new \RuntimeException('Only submitted sessions can be approved.');
            }

            StockTakeApproval::create([
                'business_id' => $locked->business_id,
                'session_id' => $locked->id,
                'level_no' => 1,
                'action' => 'approved',
                'remarks' => $remarks,
                'acted_by' => auth()->id(),
                'acted_at' => now(),
            ]);
            $locked->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'approval_remarks' => $remarks,
            ]);
            $this->audit->log($locked->business_id, $locked->id, 'session_approved', 'session', $locked->id, [], [
                'remarks' => $remarks,
            ]);
        });
    }

    public function reject(StockTakeSession $session, ?string $remarks = null): void
    {
        DB::transaction(function () use ($session, $remarks): void {
            $locked = StockTakeSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['submitted', 'under_review'], true)) {
                throw new \RuntimeException('Only submitted sessions can be rejected.');
            }

            StockTakeApproval::create([
                'business_id' => $locked->business_id,
                'session_id' => $locked->id,
                'level_no' => 1,
                'action' => 'rejected',
                'remarks' => $remarks,
                'acted_by' => auth()->id(),
                'acted_at' => now(),
            ]);
            $locked->update(['status' => 'rejected', 'approval_remarks' => $remarks]);
            $this->audit->log($locked->business_id, $locked->id, 'session_rejected', 'session', $locked->id, [], [
                'remarks' => $remarks,
            ]);
        });
    }

    public function post(StockTakeSession $session): int
    {
        $settings = $this->settings->all((int) $session->business_id);
        $updateShared = $this->settings->bool($settings, 'post_to_shared_inventory', true);

        return DB::transaction(function () use ($session, $updateShared): int {
            $locked = StockTakeSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'approved') {
                throw new \RuntimeException('Only approved sessions can be posted.');
            }

            $posted = 0;
            foreach ($locked->lines()->where('is_counted', 1)->orderBy('id')->lockForUpdate()->get() as $line) {
                $before = $this->inventory->currentQty(
                    (int) $line->product_id,
                    $line->variation_id ? (int) $line->variation_id : null,
                    (int) $locked->location_id,
                    $locked->store_id ? (int) $locked->store_id : null
                );
                $after = (float) $line->final_count_qty;
                $result = ['before' => $before, 'after' => $after, 'change' => $after - $before, 'table' => 'ledger_only'];

                if ($updateShared) {
                    $result = $this->inventory->setCurrentQty(
                        (int) $line->product_id,
                        $line->product_variation_id ? (int) $line->product_variation_id : null,
                        $line->variation_id ? (int) $line->variation_id : null,
                        (int) $locked->location_id,
                        $locked->store_id ? (int) $locked->store_id : null,
                        $after
                    );
                }

                StockTakeInventoryMovement::create([
                    'business_id' => $locked->business_id,
                    'location_id' => $locked->location_id,
                    'store_id' => $locked->store_id,
                    'session_id' => $locked->id,
                    'line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'product_variation_id' => $line->product_variation_id,
                    'variation_id' => $line->variation_id,
                    'movement_type' => 'stock_take_reconciliation',
                    'before_qty' => $result['before'],
                    'adjustment_qty' => $result['change'],
                    'after_qty' => $result['after'],
                    'unit_cost' => $line->unit_cost,
                    'value_change' => $result['change'] * (float) $line->unit_cost,
                    'source_table' => $result['table'],
                    'reference_no' => $locked->stock_take_no,
                    'posted_by' => auth()->id(),
                    'posted_at' => now(),
                ]);
                $posted++;
            }

            $locked->update([
                'status' => 'posted',
                'posted_by' => auth()->id(),
                'posted_at' => now(),
                'closed_at' => now(),
            ]);
            $this->audit->log($locked->business_id, $locked->id, 'reconciliation_posted', 'session', $locked->id, [], [
                'lines' => $posted,
                'shared_inventory_updated' => $updateShared,
            ]);

            return $posted;
        });
    }
}
