<?php

namespace Modules\BankingMicrofinance\Services;

use Illuminate\Support\Facades\DB;
use Modules\BankingMicrofinance\Entities\OfflineCollectionBatch;
use Modules\BankingMicrofinance\Entities\OfflineCollectionLine;
use Modules\BankingMicrofinance\Entities\FieldReceipt;

class FieldCollectionService
{
    public function createOfflineBatch(array $payload): OfflineCollectionBatch
    {
        return DB::transaction(function () use ($payload) {
            $lines = $payload['lines'] ?? [];
            unset($payload['lines']);

            $batch = OfflineCollectionBatch::create(array_merge($payload, [
                'status' => $payload['status'] ?? 'pending',
                'total_amount' => collect($lines)->sum(fn ($line) => (float) ($line['amount'] ?? 0)),
                'line_count' => count($lines),
            ]));

            foreach ($lines as $line) {
                $created = OfflineCollectionLine::create(array_merge($line, [
                    'offline_collection_batch_id' => $batch->id,
                    'status' => 'pending',
                ]));

                FieldReceipt::create([
                    'offline_collection_batch_id' => $batch->id,
                    'offline_collection_line_id' => $created->id,
                    'receipt_no' => $line['receipt_no'] ?? null,
                    'member_id' => $line['member_id'] ?? null,
                    'loan_account_id' => $line['loan_account_id'] ?? null,
                    'receipt_date' => $payload['collection_date'] ?? now()->toDateString(),
                    'amount' => $line['amount'] ?? 0,
                    'status' => 'pending_verification',
                ]);
            }

            return $batch;
        });
    }

    public function approveBatch(OfflineCollectionBatch $batch, int $userId): OfflineCollectionBatch
    {
        $batch->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        OfflineCollectionLine::where('offline_collection_batch_id', $batch->id)->update(['status' => 'approved']);
        return $batch->refresh();
    }

    public function rejectBatch(OfflineCollectionBatch $batch, int $userId, ?string $reason = null): OfflineCollectionBatch
    {
        $batch->update(['status' => 'rejected', 'rejected_by' => $userId, 'rejected_at' => now(), 'reject_reason' => $reason]);
        OfflineCollectionLine::where('offline_collection_batch_id', $batch->id)->update(['status' => 'rejected']);
        return $batch->refresh();
    }
}
