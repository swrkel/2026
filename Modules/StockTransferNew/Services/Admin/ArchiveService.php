<?php

namespace Modules\StockTransferNew\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\StockTransferNew\Entities\ArchiveRestoreRequest;
use Modules\StockTransferNew\Entities\ArchiveRun;
use Modules\StockTransferNew\Entities\ArchiveRunLine;

class ArchiveService
{
    public function summary(int $businessId): array
    {
        return [
            'preview_runs' => $this->countRuns($businessId, 'preview'),
            'archived_runs' => $this->countRuns($businessId, 'archived'),
            'restore_requests' => Schema::hasTable('stn_archive_restore_requests')
                ? ArchiveRestoreRequest::where('business_id', $businessId)->where('status', 'requested')->count()
                : 0,
            'eligible_completed' => $this->eligibleQuery($businessId, now()->subYear()->endOfYear()->toDateString())->count(),
        ];
    }

    public function preview(array $filters): ArchiveRun
    {
        $businessId = (int) $filters['business_id'];
        $until = $filters['archive_until'];

        return DB::transaction(function () use ($filters, $businessId, $until) {
            $run = ArchiveRun::create([
                'business_id' => $businessId,
                'location_id' => $filters['location_id'] ?? null,
                'store_id' => $filters['store_id'] ?? null,
                'archive_year' => (int) date('Y', strtotime($until)),
                'archive_until' => $until,
                'status' => 'preview',
                'preview_by' => auth()->id(),
                'preview_at' => now(),
                'remarks' => $filters['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $query = $this->eligibleQuery($businessId, $until)
                ->when(!empty($filters['location_id']), function ($q) use ($filters) {
                    $q->where(function ($inner) use ($filters) {
                        $inner->where('from_location_id', $filters['location_id'])
                              ->orWhere('to_location_id', $filters['location_id']);
                    });
                })
                ->when(!empty($filters['store_id']), function ($q) use ($filters) {
                    $q->where(function ($inner) use ($filters) {
                        $inner->where('from_store_id', $filters['store_id'])
                              ->orWhere('to_store_id', $filters['store_id']);
                    });
                })
                ->limit(1000)
                ->get();

            $totalQty = 0;
            $totalValue = 0;
            $totalLines = 0;

            foreach ($query as $transfer) {
                $lineCount = $this->lineCount((int) $transfer->id);
                $qty = $this->lineQty((int) $transfer->id);
                $value = $this->lineValue((int) $transfer->id);
                $totalLines += $lineCount;
                $totalQty += $qty;
                $totalValue += $value;

                ArchiveRunLine::create([
                    'archive_run_id' => $run->id,
                    'transfer_id' => $transfer->id,
                    'transfer_no' => $transfer->transfer_no ?? $transfer->reference_no ?? ('TR-' . $transfer->id),
                    'transfer_date' => $transfer->transfer_date ?? $transfer->created_at,
                    'from_location_id' => $transfer->from_location_id ?? null,
                    'from_store_id' => $transfer->from_store_id ?? null,
                    'to_location_id' => $transfer->to_location_id ?? null,
                    'to_store_id' => $transfer->to_store_id ?? null,
                    'status' => $transfer->status ?? 'completed',
                    'line_count' => $lineCount,
                    'total_qty' => $qty,
                    'total_value' => $value,
                    'archive_decision' => 'eligible',
                ]);
            }

            $run->update([
                'total_completed_transfers' => $query->count(),
                'total_lines' => $totalLines,
                'total_qty' => $totalQty,
                'total_value' => $totalValue,
                'checksum' => sha1($businessId . '|' . $until . '|' . $query->count() . '|' . Str::random(12)),
            ]);

            return $run->fresh('lines');
        });
    }

    public function execute(ArchiveRun $run): ArchiveRun
    {
        if ($run->status !== 'preview') {
            throw new \RuntimeException('Only preview archive runs can be executed.');
        }

        $run->update([
            'status' => 'archived',
            'archived_by' => auth()->id(),
            'archived_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return $run->fresh();
    }

    public function createRestoreRequest(array $data): ArchiveRestoreRequest
    {
        return ArchiveRestoreRequest::create([
            'business_id' => (int) $data['business_id'],
            'archive_run_id' => $data['archive_run_id'] ?? null,
            'transfer_id' => $data['transfer_id'] ?? null,
            'transfer_no' => $data['transfer_no'] ?? null,
            'reason' => $data['reason'],
            'status' => 'requested',
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);
    }

    protected function countRuns(int $businessId, string $status): int
    {
        return Schema::hasTable('stn_archive_runs')
            ? ArchiveRun::where('business_id', $businessId)->where('status', $status)->count()
            : 0;
    }

    protected function eligibleQuery(int $businessId, string $until)
    {
        $table = Schema::hasTable('stn_transfers') ? 'stn_transfers' : 'stock_transfer_new_transfers';
        return DB::table($table)
            ->where('business_id', $businessId)
            ->whereIn('status', ['completed', 'received', 'closed'])
            ->whereDate(DB::raw('COALESCE(transfer_date, created_at)'), '<=', $until);
    }

    protected function lineCount(int $transferId): int
    {
        if (!Schema::hasTable('stn_transfer_lines')) {
            return 0;
        }
        return DB::table('stn_transfer_lines')->where('transfer_id', $transferId)->count();
    }

    protected function lineQty(int $transferId): float
    {
        if (!Schema::hasTable('stn_transfer_lines')) {
            return 0;
        }
        return (float) DB::table('stn_transfer_lines')->where('transfer_id', $transferId)->sum(DB::raw('COALESCE(received_qty, approved_qty, requested_qty, quantity, 0)'));
    }

    protected function lineValue(int $transferId): float
    {
        if (!Schema::hasTable('stn_transfer_lines')) {
            return 0;
        }
        return (float) DB::table('stn_transfer_lines')->where('transfer_id', $transferId)->sum(DB::raw('COALESCE(line_total, total, 0)'));
    }
}
