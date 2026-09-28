<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewBusinessCustomerHistory;

class MembershipNewBusinessHistoryService
{
    public function addLedgerEntry(array $data): MembershipNewBusinessCustomerHistory
    {
        return DB::transaction(function () use ($data) {
            $businessId = (int) $data['business_id'];
            $mapId = (int) $data['member_business_map_id'];

            $lastBalance = (float) MembershipNewBusinessCustomerHistory::forBusiness($businessId)
                ->where('member_business_map_id', $mapId)
                ->orderByDesc('id')
                ->value('balance');

            $debit = (float) ($data['debit'] ?? 0);
            $credit = (float) ($data['credit'] ?? 0);

            $data['balance'] = round($lastBalance + $debit - $credit, 4);
            $data['transaction_date'] = $data['transaction_date'] ?? now();

            return MembershipNewBusinessCustomerHistory::create($data);
        });
    }

    public function recordSale(int $businessId, int $mapId, float $amount, ?string $referenceType = null, ?int $referenceId = null): MembershipNewBusinessCustomerHistory
    {
        return $this->addLedgerEntry([
            'business_id' => $businessId,
            'member_business_map_id' => $mapId,
            'transaction_type' => 'sale',
            'debit' => $amount,
            'credit' => 0,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => 'Sale recorded for this business only',
        ]);
    }

    public function recordPayment(int $businessId, int $mapId, float $amount, ?string $referenceType = null, ?int $referenceId = null): MembershipNewBusinessCustomerHistory
    {
        return $this->addLedgerEntry([
            'business_id' => $businessId,
            'member_business_map_id' => $mapId,
            'transaction_type' => 'payment',
            'debit' => 0,
            'credit' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => 'Payment recorded for this business only',
        ]);
    }
}
