<?php

namespace Modules\Finance\Services\Transactions;

use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountGroup;

/**
 * Finance source-account balance guard.
 *
 * Business rule (17 Sep 2026):
 * - Accounts linked to Cash, Card/Cards, or Cheques in Hand account groups
 *   cannot pay, transfer or deposit more than their available balance.
 * - Every other account may be used for an amount greater than its current
 *   balance (the resulting overpayment/negative balance is allowed).
 *
 * This service is intentionally server-side so browser changes cannot bypass
 * the protected-group rule.
 */
class FinanceTransactionGuard
{
    /**
     * Kept for backward compatibility with older Finance views/controllers.
     *
     * Overpayment is no longer a business/subscription-wide permission. It is
     * decided per source account by requiresAvailableBalance(). Returning false
     * prevents an old global flag from bypassing a protected Cash/Card/Cheques
     * in Hand account.
     */
    public function allowsOverDeposit(int $businessId): bool
    {
        return false;
    }

    /**
     * Determine whether the selected source account belongs to one of the
     * protected account groups.
     */
    public function requiresAvailableBalance(int $businessId, int $accountId): bool
    {
        if ($businessId <= 0 || $accountId <= 0) {
            return false;
        }

        $account = Account::where('business_id', $businessId)
            ->NotClosed()
            ->find($accountId);

        if (empty($account) || empty($account->asset_type)) {
            return false;
        }

        $groupName = (string) AccountGroup::where('business_id', $businessId)
            ->where('id', $account->asset_type)
            ->value('name');

        return $this->isProtectedGroupName($groupName);
    }

    /**
     * Check one source account.
     *
     * @return array{allowed:bool,over_deposit_allowed:bool,balance_restricted:bool,account_id:int,account_name:string,available:float,required:float,message:string}
     */
    public function checkSourceBalance(int $businessId, int $accountId, float $requiredAmount, bool $legacyAllowOverDeposit = false): array
    {
        $requiredAmount = round(max(0, $requiredAmount), 6);

        $account = Account::where('business_id', $businessId)
            ->NotClosed()
            ->find($accountId);

        if (empty($account)) {
            return [
                'allowed' => false,
                'over_deposit_allowed' => false,
                'balance_restricted' => false,
                'account_id' => $accountId,
                'account_name' => 'Selected account',
                'available' => 0.0,
                'required' => $requiredAmount,
                'message' => 'Selected source account is not available or has been closed. Please select an active account and try again.',
            ];
        }

        $balanceRestricted = $this->requiresAvailableBalance($businessId, $accountId);

        // Overpayments are intentionally allowed on every non-protected group.
        if (! $balanceRestricted) {
            return [
                'allowed' => true,
                'over_deposit_allowed' => true,
                'balance_restricted' => false,
                'account_id' => $accountId,
                'account_name' => (string) $account->name,
                'available' => 0.0,
                'required' => $requiredAmount,
                'message' => '',
            ];
        }

        $available = (float) Account::getAccountBalance($accountId, null, null, false);

        if ($requiredAmount <= ($available + 0.000001)) {
            return [
                'allowed' => true,
                'over_deposit_allowed' => false,
                'balance_restricted' => true,
                'account_id' => $accountId,
                'account_name' => (string) $account->name,
                'available' => $available,
                'required' => $requiredAmount,
                'message' => '',
            ];
        }

        return [
            'allowed' => false,
            'over_deposit_allowed' => false,
            'balance_restricted' => true,
            'account_id' => $accountId,
            'account_name' => (string) $account->name,
            'available' => $available,
            'required' => $requiredAmount,
            'message' => sprintf(
                'Insufficient Balance: %s is linked to a protected Cash, Card or Cheques in Hand account group and has only %s available, but %s is required.',
                (string) $account->name,
                number_format($available, 2, '.', ','),
                number_format($requiredAmount, 2, '.', ',')
            ),
        ];
    }

    /**
     * Check several source accounts (for example a cheque deposit containing
     * rows held in multiple Cheques-in-Hand child accounts).
     */
    public function checkMultipleSourceBalances(int $businessId, array $requiredByAccount, bool $legacyAllowOverDeposit = false): array
    {
        foreach ($requiredByAccount as $accountId => $amount) {
            $result = $this->checkSourceBalance(
                $businessId,
                (int) $accountId,
                (float) $amount,
                false
            );

            if (! $result['allowed']) {
                return $result;
            }
        }

        return [
            'allowed' => true,
            'over_deposit_allowed' => false,
            'balance_restricted' => false,
            'message' => '',
        ];
    }

    private function isProtectedGroupName(string $groupName): bool
    {
        $normalized = strtolower(trim($groupName));
        $normalized = str_replace(["’", "‘", "`", "´"], "'", $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?: '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?: '');

        return in_array($normalized, [
            'cash',
            'cash account',
            'card',
            'cards',
            'card account',
            'cards account',
            'cheque in hand',
            'cheques in hand',
            'cheque in hand customer s',
            'cheques in hand customer s',
            'cheque in hand customers',
            'cheques in hand customers',
        ], true);
    }
}
