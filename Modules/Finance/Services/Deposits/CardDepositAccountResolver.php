<?php

namespace Modules\Finance\Services\Deposits;

use Illuminate\Support\Collection;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;

/**
 * Resolves the selectable source accounts for a Card Deposit.
 *
 * The Finance module must not depend on one legacy parent-account name.
 * Tenant databases can legitimately contain Card-group accounts without a
 * parent named exactly "Cards (Credit Debit) Account". This resolver uses the
 * Finance-owned entities and the account-group relationship as the source of
 * truth, while still supporting the legacy parent/child structure.
 */
class CardDepositAccountResolver
{
    private const PARENT_NAMES = [
        'cards (credit debit) account',
        'card (credit debit) account',
        'cards credit debit account',
        'card credit debit account',
    ];

    private const GROUP_NAMES = [
        'card',
        'cards',
        'card account',
        'cards account',
    ];

    /**
     * Return active Card accounts for the current business as id => name.
     */
    public function optionsForBusiness(int $businessId): Collection
    {
        if ($businessId <= 0) {
            return collect();
        }

        $cardGroupIds = AccountGroup::query()
            ->where('business_id', $businessId)
            ->whereRaw(
                "LOWER(REPLACE(TRIM(name), '  ', ' ')) IN (?, ?, ?, ?)",
                self::GROUP_NAMES
            )
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->values();

        $parentIds = Account::query()
            ->where('business_id', $businessId)
            ->notClosed()
            ->whereRaw(
                "LOWER(REPLACE(TRIM(name), '  ', ' ')) IN (?, ?, ?, ?)",
                self::PARENT_NAMES
            )
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($cardGroupIds->isEmpty() && $parentIds->isEmpty()) {
            return collect();
        }

        $query = Account::query()
            ->where('business_id', $businessId)
            ->notClosed()
            ->where(function ($query) use ($cardGroupIds, $parentIds): void {
                $hasCondition = false;

                if ($parentIds->isNotEmpty()) {
                    $query->whereIn('parent_account_id', $parentIds->all());
                    $hasCondition = true;
                }

                if ($cardGroupIds->isNotEmpty()) {
                    $method = $hasCondition ? 'orWhereIn' : 'whereIn';
                    $query->{$method}('asset_type', $cardGroupIds->all());
                }
            });

        if ($parentIds->isNotEmpty()) {
            $query->whereNotIn('id', $parentIds->all());
        }

        // Prefer operational/sub accounts. Some older tenant databases did not
        // populate is_main_account consistently, therefore null is accepted.
        $strictOptions = (clone $query)
            ->where(function ($query): void {
                $query->where('is_main_account', 0)
                    ->orWhereNull('is_main_account');
            })
            ->orderBy('name')
            ->pluck('name', 'id');

        if ($strictOptions->isNotEmpty()) {
            return $strictOptions;
        }

        // Compatibility fallback for older tenants that marked every Card-group
        // account as a main account. The known legacy parent itself remains
        // excluded, so only usable source accounts are returned.
        return $query->orderBy('name')->pluck('name', 'id');
    }

    public function isCardAccount(int $businessId, int $accountId): bool
    {
        if ($accountId <= 0) {
            return false;
        }

        return $this->optionsForBusiness($businessId)->has($accountId);
    }
}
