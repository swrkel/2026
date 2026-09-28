<?php

namespace Modules\DigitalWallet\Services\Rules;

use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletRule;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;

class DigitalWalletRuleService
{
    public function canCharge(DigitalWallet $wallet, float $amount): array
    {
        if ($wallet->is_locked) {
            return ['allowed' => false, 'message' => 'Wallet is locked. '.$wallet->locked_reason];
        }

        if ($wallet->status !== 'active') {
            return ['allowed' => false, 'message' => 'Wallet is not active.'];
        }

        $availableWithCredit = (float) $wallet->available_balance + (float) ($wallet->credit_limit ?? 0);
        if (! config('digitalwallet.allow_negative_balance', false) && $availableWithCredit < $amount) {
            return ['allowed' => false, 'message' => 'Insufficient wallet balance.'];
        }

        if ($wallet->daily_spend_limit !== null) {
            $spentToday = DigitalWalletTransaction::where('wallet_id', $wallet->id)
                ->whereIn('transaction_type', ['charge', 'transfer_out'])
                ->whereDate('created_at', today())
                ->sum('amount');
            if (($spentToday + $amount) > (float) $wallet->daily_spend_limit) {
                return ['allowed' => false, 'message' => 'Daily wallet spending limit exceeded.'];
            }
        }

        if ($wallet->monthly_spend_limit !== null) {
            $spentMonth = DigitalWalletTransaction::where('wallet_id', $wallet->id)
                ->whereIn('transaction_type', ['charge', 'transfer_out'])
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum('amount');
            if (($spentMonth + $amount) > (float) $wallet->monthly_spend_limit) {
                return ['allowed' => false, 'message' => 'Monthly wallet spending limit exceeded.'];
            }
        }

        $rules = DigitalWalletRule::where('is_active', true)
            ->where(function ($query) use ($wallet) {
                $query->whereNull('wallet_type')->orWhere('wallet_type', $wallet->wallet_type);
            })
            ->get();

        foreach ($rules as $rule) {
            if ($rule->minimum_balance !== null && ((float) $wallet->available_balance - $amount) < (float) $rule->minimum_balance) {
                return ['allowed' => false, 'message' => 'Minimum balance rule prevents this charge.'];
            }
            if ($rule->daily_limit !== null && $amount > (float) $rule->daily_limit) {
                return ['allowed' => false, 'message' => 'Rule daily limit prevents this charge.'];
            }
        }

        return ['allowed' => true, 'message' => 'Approved'];
    }

    public function needsApproval(float $amount, ?string $walletType = null): bool
    {
        return DigitalWalletRule::where('is_active', true)
            ->where(function ($query) use ($walletType) {
                $query->whereNull('wallet_type')->orWhere('wallet_type', $walletType);
            })
            ->whereNotNull('approval_threshold')
            ->where('approval_threshold', '<=', $amount)
            ->exists();
    }
}
