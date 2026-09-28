<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferReservationControlService
{
    public function summary(array $filters = []): array
    {
        $row = DB::query()->fromSub($this->reservationQuery($filters), 'x')
            ->selectRaw('COUNT(*) as total_reservations')
            ->selectRaw("SUM(CASE WHEN reservation_status = 'active' THEN 1 ELSE 0 END) as active_reservations")
            ->selectRaw("SUM(CASE WHEN reservation_status = 'expired' THEN 1 ELSE 0 END) as expired_reservations")
            ->selectRaw('COALESCE(SUM(reserved_qty),0) as reserved_qty')
            ->selectRaw('COALESCE(SUM(pending_qty),0) as pending_qty')
            ->selectRaw('COALESCE(SUM(reserved_value),0) as reserved_value')
            ->first();

        return [
            'total_reservations' => (int) ($row->total_reservations ?? 0),
            'active_reservations' => (int) ($row->active_reservations ?? 0),
            'expired_reservations' => (int) ($row->expired_reservations ?? 0),
            'reserved_qty' => (float) ($row->reserved_qty ?? 0),
            'pending_qty' => (float) ($row->pending_qty ?? 0),
            'reserved_value' => (float) ($row->reserved_value ?? 0),
        ];
    }

    public function rows(array $filters = [], int $limit = 500)
    {
        return $this->reservationQuery($filters)
            ->orderByRaw("CASE WHEN reservation_status = 'active' THEN 0 WHEN reservation_status = 'expired' THEN 1 ELSE 2 END")
            ->orderByDesc('r.reservation_date')
            ->orderByDesc('r.id')
            ->limit($limit)
            ->get();
    }

    public function candidates(array $filters = [], int $limit = 250)
    {
        $transferTable = $this->transferTable();
        $lineTable = $this->lineTable();

        $query = DB::table($transferTable . ' as st')
            ->join($lineTable . ' as sl', 'sl.transfer_id', '=', 'st.id')
            ->leftJoin('stock_transfer_new_reservations as r', function ($join) {
                $join->on('r.transfer_id', '=', 'st.id')
                    ->on('r.transfer_line_id', '=', 'sl.id')
                    ->whereNotIn('r.reservation_status', ['cancelled', 'released']);
            })
            ->whereNull('r.id')
            ->whereIn('st.status', ['draft', 'requested', 'approved'])
            ->select([
                'st.id as transfer_id', 'sl.id as transfer_line_id', 'st.transfer_no', 'st.transaction_date', 'st.status',
                'st.from_business_id', 'st.from_location_id', 'st.from_store_id',
                'st.to_business_id', 'st.to_location_id', 'st.to_store_id',
                'sl.product_id', 'sl.variation_id', 'sl.sku', 'sl.product_name', 'sl.batch_no', 'sl.expiry_date',
            ])
            ->selectRaw('COALESCE(sl.quantity, sl.transfer_qty, 0) as requested_qty')
            ->selectRaw('COALESCE(sl.unit_cost, sl.unit_price, 0) as unit_cost')
            ->selectRaw('COALESCE(sl.quantity, sl.transfer_qty, 0) * COALESCE(sl.unit_cost, sl.unit_price, 0) as requested_value');

        foreach (['from_business_id', 'from_location_id', 'from_store_id', 'product_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('st.' . $field, $filters[$field]);
            }
        }
        if (! empty($filters['status'])) {
            $query->where('st.status', $filters['status']);
        }

        return $query->orderByDesc('st.transaction_date')->limit($limit)->get();
    }

    public function reserveCandidate(int $transferLineId, int $userId, ?int $expiryHours = null): int
    {
        $candidate = $this->candidates([], 1000)->firstWhere('transfer_line_id', $transferLineId);
        if (! $candidate) {
            throw new RuntimeException('Selected transfer line is not eligible for reservation.');
        }

        return DB::transaction(function () use ($candidate, $userId, $expiryHours) {
            $qty = (float) $candidate->requested_qty;
            $unitCost = (float) $candidate->unit_cost;
            $reservationId = DB::table('stock_transfer_new_reservations')->insertGetId([
                'reservation_no' => $this->nextReservationNo(),
                'transfer_id' => $candidate->transfer_id,
                'transfer_line_id' => $candidate->transfer_line_id,
                'transfer_no' => $candidate->transfer_no,
                'reservation_date' => now()->toDateString(),
                'reservation_status' => 'active',
                'from_business_id' => $candidate->from_business_id,
                'from_location_id' => $candidate->from_location_id,
                'from_store_id' => $candidate->from_store_id,
                'to_business_id' => $candidate->to_business_id,
                'to_location_id' => $candidate->to_location_id,
                'to_store_id' => $candidate->to_store_id,
                'product_id' => $candidate->product_id,
                'variation_id' => $candidate->variation_id,
                'sku' => $candidate->sku,
                'product_name' => $candidate->product_name,
                'batch_no' => $candidate->batch_no,
                'expiry_date' => $candidate->expiry_date,
                'reserved_qty' => $qty,
                'pending_qty' => $qty,
                'unit_cost' => $unitCost,
                'reserved_value' => $qty * $unitCost,
                'expires_at' => $expiryHours ? now()->addHours($expiryHours) : null,
                'created_by' => $userId,
                'updated_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->logAction($reservationId, $candidate->transfer_id, 'reserved', $qty, $qty * $unitCost, 'Stock reserved for transfer line.', $userId);
            return $reservationId;
        });
    }

    public function release(int $reservationId, string $reason, int $userId): void
    {
        DB::transaction(function () use ($reservationId, $reason, $userId) {
            $reservation = DB::table('stock_transfer_new_reservations')->where('id', $reservationId)->lockForUpdate()->first();
            if (! $reservation) {
                throw new RuntimeException('Reservation not found.');
            }
            if (! in_array($reservation->reservation_status, ['active', 'expired'], true)) {
                throw new RuntimeException('Only active or expired reservations can be released.');
            }

            DB::table('stock_transfer_new_reservations')->where('id', $reservationId)->update([
                'reservation_status' => 'released',
                'released_qty' => DB::raw('pending_qty'),
                'pending_qty' => 0,
                'released_at' => now(),
                'release_reason' => $reason,
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);

            $this->logAction($reservationId, (int) $reservation->transfer_id, 'released', (float) $reservation->pending_qty, (float) $reservation->pending_qty * (float) $reservation->unit_cost, $reason, $userId);
        });
    }

    public function expireOverdue(?int $userId = null): int
    {
        $ids = DB::table('stock_transfer_new_reservations')
            ->where('reservation_status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->pluck('id');

        foreach ($ids as $id) {
            DB::table('stock_transfer_new_reservations')->where('id', $id)->update([
                'reservation_status' => 'expired',
                'updated_by' => $userId,
                'updated_at' => now(),
            ]);
            $reservation = DB::table('stock_transfer_new_reservations')->where('id', $id)->first();
            $this->logAction((int) $id, (int) $reservation->transfer_id, 'expired', (float) $reservation->pending_qty, (float) $reservation->pending_qty * (float) $reservation->unit_cost, 'Reservation expired automatically.', $userId);
        }

        return $ids->count();
    }

    public function exportCsv(array $filters = []): string
    {
        $rows = $this->rows($filters, 5000);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Reservation No', 'Transfer No', 'Date', 'Status', 'SKU', 'Product', 'Batch', 'Reserved Qty', 'Pending Qty', 'Value', 'Expires At']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->reservation_no, $row->transfer_no, $row->reservation_date, $row->reservation_status,
                $row->sku, $row->product_name, $row->batch_no, $row->reserved_qty, $row->pending_qty,
                number_format((float) $row->reserved_value, 4, '.', ''), $row->expires_at,
            ]);
        }
        rewind($handle);
        return stream_get_contents($handle);
    }

    private function reservationQuery(array $filters)
    {
        $query = DB::table('stock_transfer_new_reservations as r')->select('r.*');
        foreach (['reservation_status', 'from_business_id', 'from_location_id', 'from_store_id', 'product_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where('r.' . $field, $filters[$field]);
            }
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('r.reservation_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('r.reservation_date', '<=', $filters['date_to']);
        }
        return $query;
    }

    private function nextReservationNo(): string
    {
        $last = DB::table('stock_transfer_new_reservations')->lockForUpdate()->max('id');
        return 'STN-RES-' . now()->format('ymd') . '-' . str_pad((string) ((int) $last + 1), 5, '0', STR_PAD_LEFT);
    }

    private function logAction(int $reservationId, int $transferId, string $type, float $qty, float $value, ?string $remarks, ?int $userId): void
    {
        DB::table('stock_transfer_new_reservation_actions')->insert([
            'reservation_id' => $reservationId,
            'transfer_id' => $transferId,
            'action_type' => $type,
            'action_qty' => $qty,
            'action_value' => $value,
            'remarks' => $remarks,
            'action_by' => $userId,
            'action_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function transferTable(): string
    {
        return DB::getSchemaBuilder()->hasTable('stock_transfer_new_transfers') ? 'stock_transfer_new_transfers' : 'stock_transfer_new';
    }

    private function lineTable(): string
    {
        return DB::getSchemaBuilder()->hasTable('stock_transfer_new_transfer_lines') ? 'stock_transfer_new_transfer_lines' : 'stock_transfer_new_lines';
    }
}
