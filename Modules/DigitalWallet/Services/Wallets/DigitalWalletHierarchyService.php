<?php

namespace Modules\DigitalWallet\Services\Wallets;

use Modules\DigitalWallet\Entities\DigitalWallet;

class DigitalWalletHierarchyService
{
    public function summary(): array
    {
        return [
            'business_wallets' => DigitalWallet::where('hierarchy_level', 'business')->count(),
            'branch_wallets' => DigitalWallet::where('hierarchy_level', 'branch')->count(),
            'department_wallets' => DigitalWallet::where('hierarchy_level', 'department')->count(),
            'user_wallets' => DigitalWallet::where('hierarchy_level', 'user')->count(),
            'locked_wallets' => DigitalWallet::where('is_locked', true)->count(),
            'with_limits' => DigitalWallet::where(function ($query) {
                $query->whereNotNull('daily_spend_limit')->orWhereNotNull('monthly_spend_limit');
            })->count(),
        ];
    }

    public function tree()
    {
        return DigitalWallet::with('childWallets')
            ->whereNull('parent_wallet_id')
            ->orderBy('hierarchy_level')
            ->orderBy('wallet_name')
            ->get();
    }

    public function levelOptions(): array
    {
        return [
            'business' => 'Business Wallet',
            'branch' => 'Branch Wallet',
            'department' => 'Department Wallet',
            'user' => 'User Wallet',
            'shared' => 'Shared Wallet',
        ];
    }
}
