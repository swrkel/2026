<?php

namespace Modules\Finance\Services\Purchases;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps purchase postings in the Finished Goods account on the debit side.
 *
 * This service is intentionally owned by Finance. It observes the shared
 * account transaction model, so the Purchase module does not need to be
 * edited and parallel Purchase-module development is not affected.
 */
class PurchaseFinishedGoodsDebitNormalizer
{
    /** @var array<int, int|null> */
    private array $finishedGoodsAccountIds = [];

    /**
     * Normalize a newly saved core account transaction when it represents a
     * purchase posting to the Finished Goods account.
     *
     * @param  object  $accountTransaction
     */
    public function normalize(object $accountTransaction): void
    {
        $accountTransactionId = (int) ($accountTransaction->id ?? 0);
        $transactionId = (int) ($accountTransaction->transaction_id ?? 0);
        $accountId = (int) ($accountTransaction->account_id ?? 0);
        $businessId = (int) ($accountTransaction->business_id ?? 0);
        $type = strtolower((string) ($accountTransaction->type ?? ''));

        if (
            $accountTransactionId <= 0 ||
            $transactionId <= 0 ||
            $accountId <= 0 ||
            $businessId <= 0 ||
            $type === 'debit'
        ) {
            return;
        }

        try {
            $finishedGoodsAccountId = $this->finishedGoodsAccountId($businessId);
            if ($finishedGoodsAccountId === null || $accountId !== $finishedGoodsAccountId) {
                return;
            }

            $transactionType = DB::table('transactions')
                ->where('id', $transactionId)
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->value('type');

            if ($transactionType !== 'purchase') {
                return;
            }

            // Query builder update avoids firing the model event again.
            DB::table('account_transactions')
                ->where('id', $accountTransactionId)
                ->where('business_id', $businessId)
                ->whereNull('deleted_at')
                ->update([
                    'type' => 'debit',
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $exception) {
            // Accounting creation must not make Purchase save fail. Record the
            // exception for diagnosis while leaving the original save intact.
            Log::warning('IS1830: unable to normalize Finished Goods purchase posting', [
                'account_transaction_id' => $accountTransactionId,
                'transaction_id' => $transactionId,
                'business_id' => $businessId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function finishedGoodsAccountId(int $businessId): ?int
    {
        if (array_key_exists($businessId, $this->finishedGoodsAccountIds)) {
            return $this->finishedGoodsAccountIds[$businessId];
        }

        $accountId = DB::table('accounts')
            ->where('business_id', $businessId)
            ->whereNull('deleted_at')
            ->where(function ($query): void {
                $query->whereRaw('LOWER(TRIM(name)) = ?', ['finished goods account'])
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ['finished goods accounting']);
            })
            ->orderByRaw("CASE WHEN LOWER(TRIM(name)) = 'finished goods account' THEN 0 ELSE 1 END")
            ->value('id');

        $this->finishedGoodsAccountIds[$businessId] = $accountId !== null
            ? (int) $accountId
            : null;

        return $this->finishedGoodsAccountIds[$businessId];
    }
}
