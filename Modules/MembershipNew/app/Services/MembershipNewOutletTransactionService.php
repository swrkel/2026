<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewOutletTransactionQueue;
use Modules\MembershipNew\app\Integration\MembershipNewPurchaseIntegration;

class MembershipNewOutletTransactionService
{
    public function queue(array $payload): MembershipNewOutletTransactionQueue
    {
        return MembershipNewOutletTransactionQueue::create([
            'business_id' => (int) $payload['business_id'],
            'outlet_business_id' => $payload['outlet_business_id'] ?? null,
            'member_business_map_id' => $payload['member_business_map_id'] ?? null,
            'central_member_id' => $payload['central_member_id'] ?? null,
            'member_id' => $payload['member_id'] ?? null,
            'transaction_date' => $payload['transaction_date'] ?? now(),
            'purchase_amount' => (float) ($payload['purchase_amount'] ?? 0),
            'earn_points' => (float) ($payload['earn_points'] ?? 0),
            'redeem_points' => (float) ($payload['redeem_points'] ?? 0),
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id' => $payload['reference_id'] ?? null,
            'payload' => $payload,
            'is_processed' => 0,
        ]);
    }

    public function process(int $queueId, MembershipNewPurchaseIntegration $integration): MembershipNewOutletTransactionQueue
    {
        return DB::transaction(function () use ($queueId, $integration) {
            $queue = MembershipNewOutletTransactionQueue::findOrFail($queueId);

            if (!$queue->is_processed) {
                $integration->confirm($queue->payload ?? []);
                $queue->update([
                    'is_processed' => 1,
                    'processed_at' => now(),
                    'processed_by' => auth()->id(),
                ]);
            }

            return $queue->fresh();
        });
    }
}
