<?php

namespace Modules\AirlineTicketingNew\Services\Corporate;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\CorporateAgreement;
use Modules\AirlineTicketingNew\Entities\CorporateLedger;

class CorporateCreditService
{
    public function availableCredit(int $businessId, int $customerId): float
    {
        $agreement = CorporateAgreement::query()
            ->where('business_id', $businessId)
            ->where('corporate_customer_id', $customerId)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', now())
            ->where(function ($q) {
                $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', now());
            })
            ->latest('id')
            ->first();

        $balance = (float) CorporateLedger::query()
            ->where('business_id', $businessId)
            ->where('corporate_customer_id', $customerId)
            ->latest('id')
            ->value('balance');

        return max(0, (float)($agreement?->credit_limit ?? 0) - $balance);
    }

    public function assertAvailable(int $businessId, int $customerId, float $amount): void
    {
        if ($this->availableCredit($businessId, $customerId) < $amount) {
            throw ValidationException::withMessages([
                'corporate_customer_id' => 'Corporate customer credit limit exceeded.',
            ]);
        }
    }

    public function postDebit(array $data): CorporateLedger
    {
        return DB::transaction(function () use ($data): CorporateLedger {
            $lastBalance = (float) CorporateLedger::query()
                ->where('business_id', $data['business_id'])
                ->where('corporate_customer_id', $data['corporate_customer_id'])
                ->lockForUpdate()
                ->latest('id')
                ->value('balance');

            $data['balance'] = round($lastBalance + (float)$data['debit'] - (float)($data['credit'] ?? 0), 4);

            return CorporateLedger::query()->create($data);
        });
    }
}
