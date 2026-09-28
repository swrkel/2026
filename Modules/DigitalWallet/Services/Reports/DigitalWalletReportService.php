<?php

namespace Modules\DigitalWallet\Services\Reports;

use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletApprovalRequest;
use Modules\DigitalWallet\Entities\DigitalWalletLedgerEntry;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;
use Modules\DigitalWallet\Entities\DigitalWalletTransfer;
use Modules\DigitalWallet\Entities\DigitalWalletType;

class DigitalWalletReportService
{
    public function dashboard(): array
    {
        return [
            'total_wallets' => DigitalWallet::count(),
            'active_wallets' => DigitalWallet::where('status', 'active')->count(),
            'business_wallets' => DigitalWallet::where('hierarchy_level', 'business')->count(),
            'branch_wallets' => DigitalWallet::where('hierarchy_level', 'branch')->count(),
            'department_wallets' => DigitalWallet::where('hierarchy_level', 'department')->count(),
            'user_wallets' => DigitalWallet::where('hierarchy_level', 'user')->count(),
            'wallet_types' => DigitalWalletType::count(),
            'available_balance' => DigitalWallet::sum('available_balance'),
            'reserved_balance' => DigitalWallet::sum('reserved_balance'),
            'transactions_today' => DigitalWalletTransaction::whereDate('created_at', today())->count(),
            'topups_today' => DigitalWalletTransaction::where('transaction_type', 'topup')->whereDate('created_at', today())->sum('amount'),
            'charges_today' => DigitalWalletTransaction::where('transaction_type', 'charge')->whereDate('created_at', today())->sum('amount'),
            'transfers_today' => DigitalWalletTransfer::whereDate('created_at', today())->sum('amount'),
            'pending_approvals' => DigitalWalletApprovalRequest::where('status', 'pending')->count(),
            'low_balance_wallets' => DigitalWallet::whereNotNull('low_balance_threshold')->whereColumn('available_balance', '<=', 'low_balance_threshold')->count(),
            'locked_wallets' => DigitalWallet::where('is_locked', true)->count(),
        ];
    }

    public function transactions(array $filters = [])
    {
        return DigitalWalletTransaction::with('wallet')
            ->when($filters['from_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to_date'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['transaction_type'] ?? null, fn ($q, $v) => $q->where('transaction_type', $v))
            ->latest()
            ->paginate(50);
    }

    public function ledger(array $filters = [])
    {
        return DigitalWalletLedgerEntry::query()
            ->when($filters['wallet_id'] ?? null, fn ($q, $v) => $q->where('wallet_id', $v))
            ->latest('entry_date')
            ->paginate(50);
    }
}
