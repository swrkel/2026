<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferClaimService
{
    public function summary(array $filters = []): array
    {
        $row = DB::query()->fromSub($this->claimQuery($filters), 'x')
            ->selectRaw('COUNT(*) as total_claims')
            ->selectRaw("SUM(CASE WHEN claim_status IN ('draft','submitted','approved') THEN 1 ELSE 0 END) as open_claims")
            ->selectRaw('COALESCE(SUM(claimed_value),0) as claimed_value')
            ->selectRaw('COALESCE(SUM(recovered_value),0) as recovered_value')
            ->selectRaw('COALESCE(SUM(writeoff_value),0) as writeoff_value')
            ->first();

        return [
            'total_claims' => (int) ($row->total_claims ?? 0),
            'open_claims' => (int) ($row->open_claims ?? 0),
            'claimed_value' => (float) ($row->claimed_value ?? 0),
            'recovered_value' => (float) ($row->recovered_value ?? 0),
            'writeoff_value' => (float) ($row->writeoff_value ?? 0),
        ];
    }

    public function rows(array $filters = [], int $limit = 500)
    {
        return $this->claimQuery($filters)->orderByDesc('c.claim_date')->orderByDesc('c.id')->limit($limit)->get();
    }

    public function eligibleTransfers(array $filters = [])
    {
        $transferTable = $this->transferTable();
        $lineTable = $this->lineTable();

        $query = DB::table($transferTable . ' as st')
            ->leftJoin($lineTable . ' as sl', 'sl.transfer_id', '=', 'st.id')
            ->leftJoin('stock_transfer_new_claims as existing_claim', function ($join) {
                $join->on('existing_claim.transfer_id', '=', 'st.id')
                    ->whereNotIn('existing_claim.claim_status', ['cancelled']);
            })
            ->whereNull('existing_claim.id')
            ->whereIn('st.status', ['received', 'completed'])
            ->select([
                'st.id', 'st.transfer_no', 'st.transaction_date', 'st.status',
                'st.from_business_id', 'st.to_business_id', 'st.from_location_id', 'st.to_location_id',
                'st.from_store_id', 'st.to_store_id', 'st.driver_name', 'st.vehicle_no',
            ])
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(sl.quantity,0) - COALESCE(sl.received_quantity, sl.quantity, 0), 0)),0) as shortage_qty')
            ->selectRaw('COALESCE(SUM(GREATEST(COALESCE(sl.received_quantity, sl.quantity, 0) - COALESCE(sl.quantity,0), 0)),0) as excess_qty')
            ->selectRaw('COALESCE(SUM(ABS(COALESCE(sl.quantity,0) - COALESCE(sl.received_quantity, sl.quantity, 0)) * COALESCE(sl.unit_cost, sl.unit_price, 0)),0) as variance_value')
            ->groupBy('st.id', 'st.transfer_no', 'st.transaction_date', 'st.status', 'st.from_business_id', 'st.to_business_id', 'st.from_location_id', 'st.to_location_id', 'st.from_store_id', 'st.to_store_id', 'st.driver_name', 'st.vehicle_no')
            ->havingRaw('variance_value > 0');

        if (! empty($filters['date_from'])) {
            $query->whereDate('st.transaction_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('st.transaction_date', '<=', $filters['date_to']);
        }
        foreach (['from_business_id', 'to_business_id', 'from_location_id', 'to_location_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('st.' . $field, $filters[$field]);
            }
        }

        return $query->orderByDesc('st.transaction_date')->limit(500)->get();
    }

    public function createFromTransfer(int $transferId, array $data, int $userId)
    {
        return DB::transaction(function () use ($transferId, $data, $userId) {
            $transfer = $this->eligibleTransfers([])->firstWhere('id', $transferId);
            if (! $transfer) {
                throw new RuntimeException('Selected transfer is not eligible for a claim.');
            }

            $claimId = DB::table('stock_transfer_new_claims')->insertGetId([
                'claim_no' => $this->nextClaimNo(),
                'transfer_id' => $transfer->id,
                'transfer_no' => $transfer->transfer_no,
                'claim_date' => $data['claim_date'] ?? now()->toDateString(),
                'claim_type' => $data['claim_type'] ?? 'variance',
                'claim_status' => 'draft',
                'from_business_id' => $transfer->from_business_id,
                'to_business_id' => $transfer->to_business_id,
                'from_location_id' => $transfer->from_location_id,
                'to_location_id' => $transfer->to_location_id,
                'from_store_id' => $transfer->from_store_id,
                'to_store_id' => $transfer->to_store_id,
                'claimed_qty' => (float) $transfer->shortage_qty + (float) $transfer->excess_qty,
                'claimed_value' => (float) $transfer->variance_value,
                'responsible_party' => $data['responsible_party'] ?? null,
                'driver_name' => $transfer->driver_name,
                'vehicle_no' => $transfer->vehicle_no,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->createLines($claimId, $transferId);
            $this->action($claimId, 'created', $userId, 0, 0, null, $data['remarks'] ?? null);
            return $this->find($claimId);
        });
    }

    public function submit(int $id, int $userId, ?string $remarks = null): void
    {
        $this->transition($id, ['draft'], 'submitted', $userId, ['submitted_by' => $userId, 'submitted_at' => now()], 'submitted', $remarks);
    }

    public function approve(int $id, int $userId, ?string $remarks = null): void
    {
        $this->transition($id, ['submitted'], 'approved', $userId, ['approved_by' => $userId, 'approved_at' => now()], 'approved', $remarks);
    }

    public function close(int $id, int $userId, array $data): void
    {
        DB::transaction(function () use ($id, $userId, $data) {
            $claim = DB::table('stock_transfer_new_claims')->where('id', $id)->lockForUpdate()->first();
            if (! $claim || ! in_array($claim->claim_status, ['approved'], true)) {
                throw new RuntimeException('Only approved claims can be closed.');
            }
            DB::table('stock_transfer_new_claims')->where('id', $id)->update([
                'claim_status' => 'closed',
                'recovered_value' => (float) ($data['recovered_value'] ?? 0),
                'recovered_qty' => (float) ($data['recovered_qty'] ?? 0),
                'writeoff_value' => (float) ($data['writeoff_value'] ?? 0),
                'closed_by' => $userId,
                'closed_at' => now(),
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $this->action($id, 'closed', $userId, (float) ($data['recovered_qty'] ?? 0), (float) ($data['recovered_value'] ?? 0), $data['reference_no'] ?? null, $data['remarks'] ?? null);
        });
    }

    public function cancel(int $id, int $userId, string $reason): void
    {
        DB::transaction(function () use ($id, $userId, $reason) {
            $claim = DB::table('stock_transfer_new_claims')->where('id', $id)->lockForUpdate()->first();
            if (! $claim || in_array($claim->claim_status, ['closed'], true)) {
                throw new RuntimeException('Closed claims cannot be cancelled.');
            }
            DB::table('stock_transfer_new_claims')->where('id', $id)->update([
                'claim_status' => 'cancelled',
                'cancelled_by' => $userId,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $this->action($id, 'cancelled', $userId, 0, 0, null, $reason);
        });
    }

    public function find(int $id)
    {
        return $this->claimQuery([])->where('c.id', $id)->firstOrFail();
    }

    public function lines(int $id)
    {
        return DB::table('stock_transfer_new_claim_lines')->where('claim_id', $id)->orderBy('id')->get();
    }

    public function actions(int $id)
    {
        return DB::table('stock_transfer_new_claim_actions')->where('claim_id', $id)->orderBy('id')->get();
    }

    protected function createLines(int $claimId, int $transferId): void
    {
        $lineTable = $this->lineTable();
        $lines = DB::table($lineTable)->where('transfer_id', $transferId)->get();
        foreach ($lines as $line) {
            $transferred = (float) ($line->quantity ?? 0);
            $received = (float) ($line->received_quantity ?? $transferred);
            $shortage = max($transferred - $received, 0);
            $excess = max($received - $transferred, 0);
            $damage = (float) ($line->damage_quantity ?? 0);
            $unitCost = (float) ($line->unit_cost ?? $line->unit_price ?? 0);
            $value = ($shortage + $damage + $excess) * $unitCost;
            if ($value <= 0) {
                continue;
            }
            DB::table('stock_transfer_new_claim_lines')->insert([
                'claim_id' => $claimId,
                'transfer_line_id' => $line->id ?? null,
                'product_id' => $line->product_id ?? null,
                'variation_id' => $line->variation_id ?? null,
                'sku' => $line->sku ?? null,
                'product_name' => $line->product_name ?? null,
                'batch_no' => $line->batch_no ?? null,
                'expiry_date' => $line->expiry_date ?? null,
                'transferred_qty' => $transferred,
                'received_qty' => $received,
                'shortage_qty' => $shortage,
                'damage_qty' => $damage,
                'excess_qty' => $excess,
                'unit_cost' => $unitCost,
                'claim_value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function transition(int $id, array $allowed, string $newStatus, int $userId, array $extra, string $action, ?string $remarks): void
    {
        DB::transaction(function () use ($id, $allowed, $newStatus, $userId, $extra, $action, $remarks) {
            $claim = DB::table('stock_transfer_new_claims')->where('id', $id)->lockForUpdate()->first();
            if (! $claim || ! in_array($claim->claim_status, $allowed, true)) {
                throw new RuntimeException('Claim status does not allow this action.');
            }
            DB::table('stock_transfer_new_claims')->where('id', $id)->update(array_merge($extra, ['claim_status' => $newStatus, 'updated_by' => $userId, 'updated_at' => now()]));
            $this->action($id, $action, $userId, 0, 0, null, $remarks);
        });
    }

    protected function action(int $claimId, string $type, int $userId, float $qty = 0, float $amount = 0, ?string $reference = null, ?string $remarks = null): void
    {
        DB::table('stock_transfer_new_claim_actions')->insert([
            'claim_id' => $claimId,
            'action_type' => $type,
            'qty' => $qty,
            'amount' => $amount,
            'reference_no' => $reference,
            'remarks' => $remarks,
            'action_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function claimQuery(array $filters)
    {
        $query = DB::table('stock_transfer_new_claims as c')
            ->select(['c.*'])
            ->selectRaw('(c.claimed_value - c.recovered_value - c.writeoff_value) as outstanding_value');

        if (! empty($filters['status'])) {
            $query->where('c.claim_status', $filters['status']);
        }
        if (! empty($filters['claim_type'])) {
            $query->where('c.claim_type', $filters['claim_type']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('c.claim_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('c.claim_date', '<=', $filters['date_to']);
        }
        foreach (['from_business_id', 'to_business_id', 'from_location_id', 'to_location_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('c.' . $field, $filters[$field]);
            }
        }
        return $query;
    }

    protected function nextClaimNo(): string
    {
        $prefix = 'STC-' . now()->format('Ym') . '-';
        $last = DB::table('stock_transfer_new_claims')->where('claim_no', 'like', $prefix . '%')->orderByDesc('id')->value('claim_no');
        $next = $last ? ((int) substr($last, -5)) + 1 : 1;
        return $prefix . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    protected function transferTable(): string
    {
        return 'stock_transfer_new_transfers';
    }

    protected function lineTable(): string
    {
        return 'stock_transfer_new_transfer_lines';
    }
}
