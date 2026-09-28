<?php

namespace Modules\ExpensesNew\Services\Integration;

use Illuminate\Support\Facades\DB;

class ExpenseIntegrationBridgeService
{
    public function postCost(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $postingId = DB::table('expnew_integration_postings')->insertGetId([
                'business_id' => $payload['business_id'] ?? null,
                'business_location_id' => $payload['business_location_id'] ?? null,
                'source_module' => $payload['source_module'] ?? 'manual',
                'source_reference' => $payload['source_reference'] ?? null,
                'posting_type' => $payload['posting_type'] ?? 'expense',
                'amount' => $payload['amount'] ?? 0,
                'currency' => $payload['currency'] ?? 'LKR',
                'payload_json' => json_encode($payload),
                'status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return ['success' => true, 'posting_id' => $postingId, 'status' => 'received'];
        });
    }
    public function markProcessed(int $postingId, ?int $expenseId = null): void
    {
        DB::table('expnew_integration_postings')->where('id', $postingId)->update([
            'expense_id' => $expenseId,
            'status' => 'processed',
            'processed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
