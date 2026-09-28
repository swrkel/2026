<?php
namespace Modules\AirlineTicketingNew\Services\B2b;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\B2bWalletTransaction;

class B2bWalletService
{
    public function transact(array $data): B2bWalletTransaction
    {
        return DB::transaction(function () use ($data) {
            $balance = (float) B2bWalletTransaction::query()
                ->where('business_id', $data['business_id'])
                ->where('b2b_agent_id', $data['b2b_agent_id'])
                ->lockForUpdate()
                ->latest('id')
                ->value('balance_after');

            $newBalance = round(
                $balance + (float) ($data['credit'] ?? 0) - (float) ($data['debit'] ?? 0),
                4
            );

            if ($newBalance < 0) {
                throw ValidationException::withMessages([
                    'wallet' => 'Insufficient B2B agent wallet balance.',
                ]);
            }

            return B2bWalletTransaction::query()->create(array_merge($data, [
                'transaction_date' => now(),
                'balance_after' => $newBalance,
            ]));
        });
    }
}
