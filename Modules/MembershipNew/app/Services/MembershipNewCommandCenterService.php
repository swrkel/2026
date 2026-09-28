<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;

class MembershipNewCommandCenterService
{
    public function summary(int $businessId): array
    {
        return [
            'central_members' => $this->safeCount('mn_central_members'),
            'business_members' => $this->safeCount('mn_member_business_maps', ['business_id' => $businessId]),
            'business_ledger_entries' => $this->safeCount('mn_business_customer_histories', ['business_id' => $businessId]),
            'point_transactions' => $this->safeCount('mn_point_transactions', ['business_id' => $businessId]),
            'share_holdings' => $this->safeCount('mn_share_holdings', ['business_id' => $businessId]),
            'dividend_batches' => $this->safeCount('mn_dividend_batches', ['business_id' => $businessId]),
            'identity_cards' => $this->safeCount('mn_identity_cards', ['business_id' => $businessId]),
            'pending_approvals' => $this->safeCount('mn_approval_requests', ['business_id' => $businessId, 'status' => 'pending']),
            'unprocessed_outlet_transactions' => $this->safeCount('mn_outlet_transaction_queue', ['business_id' => $businessId, 'is_processed' => 0]),
        ];
    }

    private function safeCount(string $table, array $where = []): int|string
    {
        try {
            $query = DB::table($table);
            foreach ($where as $field => $value) {
                $query->where($field, $value);
            }
            return $query->count();
        } catch (\Throwable $e) {
            return 'Missing';
        }
    }
}
