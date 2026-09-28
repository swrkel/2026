<?php

namespace Modules\DigitalWallet\Services\Ledger;

use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletLedgerEntry;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;

class DigitalWalletLedgerService
{
    public function record(DigitalWallet $wallet, ?DigitalWalletTransaction $transaction, string $entryType, float $debit, float $credit, string $description = '', array $meta = []): DigitalWalletLedgerEntry
    {
        $balance = (float) $wallet->available_balance;

        return DigitalWalletLedgerEntry::create([
            'wallet_id' => $wallet->id,
            'transaction_id' => $transaction?->id,
            'entry_type' => $entryType,
            'debit' => $debit,
            'credit' => $credit,
            'balance' => $balance,
            'currency' => $wallet->currency,
            'entry_date' => now(),
            'description' => $description,
            'meta' => array_merge([
                'source_module' => $transaction?->source_module,
                'source_reference' => $transaction?->source_reference,
            ], $meta),
        ]);
    }

    public function summary(): array
    {
        return [
            'total_wallets' => DigitalWallet::count(),
            'total_available' => DigitalWallet::sum('available_balance'),
            'total_reserved' => DigitalWallet::sum('reserved_balance'),
            'transactions_today' => DigitalWalletTransaction::whereDate('created_at', today())->count(),
            'charges_today' => DigitalWalletTransaction::where('transaction_type', 'charge')->whereDate('created_at', today())->sum('amount'),
            'topups_today' => DigitalWalletTransaction::where('transaction_type', 'topup')->whereDate('created_at', today())->sum('amount'),
        ];
    }
}
