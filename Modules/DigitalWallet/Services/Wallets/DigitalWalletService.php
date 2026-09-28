<?php

namespace Modules\DigitalWallet\Services\Wallets;

use Illuminate\Support\Facades\DB;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;
use Modules\DigitalWallet\Services\Ledger\DigitalWalletLedgerService;
use Modules\DigitalWallet\Services\Rules\DigitalWalletRuleService;

class DigitalWalletService
{
    public function __construct(
        protected DigitalWalletLedgerService $ledgerService,
        protected DigitalWalletRuleService $ruleService
    ) {}

    public function createWallet(array $data): DigitalWallet
    {
        $data['wallet_code'] = $data['wallet_code'] ?? $this->nextWalletCode();
        $data['currency'] = $data['currency'] ?? config('digitalwallet.default_currency', 'LKR');
        $data['total_balance'] = ($data['available_balance'] ?? 0) + ($data['reserved_balance'] ?? 0);

        return DigitalWallet::create($data);
    }

    public function topup(DigitalWallet $wallet, float $amount, array $meta = []): DigitalWalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $meta) {
            $before = (float) $wallet->available_balance;
            $after = $before + $amount;
            $wallet->update(['available_balance' => $after, 'total_balance' => $after + (float) $wallet->reserved_balance]);

            $transaction = $this->createTransaction($wallet, 'topup', $amount, $before, $after, $meta);
            $this->ledgerService->record($wallet->refresh(), $transaction, 'topup', 0, $amount, 'Wallet top-up');

            return $transaction;
        });
    }

    public function charge(DigitalWallet $wallet, float $amount, array $meta = []): DigitalWalletTransaction
    {
        $check = $this->ruleService->canCharge($wallet, $amount);
        if (! $check['allowed']) {
            throw new \RuntimeException($check['message']);
        }

        return DB::transaction(function () use ($wallet, $amount, $meta) {
            $before = (float) $wallet->available_balance;
            $after = $before - $amount;
            $wallet->update(['available_balance' => $after, 'total_balance' => $after + (float) $wallet->reserved_balance]);

            $transaction = $this->createTransaction($wallet, 'charge', $amount, $before, $after, $meta);
            $this->ledgerService->record($wallet->refresh(), $transaction, 'charge', $amount, 0, 'Wallet charge');

            return $transaction;
        });
    }

    protected function createTransaction(DigitalWallet $wallet, string $type, float $amount, float $before, float $after, array $meta): DigitalWalletTransaction
    {
        return DigitalWalletTransaction::create([
            'transaction_no' => $meta['transaction_no'] ?? $this->nextTransactionNo(),
            'wallet_id' => $wallet->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'currency' => $wallet->currency,
            'balance_before' => $before,
            'balance_after' => $after,
            'source_module' => $meta['source_module'] ?? null,
            'source_reference' => $meta['source_reference'] ?? null,
            'external_reference' => $meta['external_reference'] ?? null,
            'business_id' => $wallet->business_id,
            'location_id' => $wallet->location_id,
            'created_by' => auth()->id(),
            'status' => 'completed',
            'note' => $meta['note'] ?? null,
            'meta' => $meta,
        ]);
    }

    public function nextWalletCode(): string
    {
        return config('digitalwallet.wallet_code_prefix', 'DW') . '-' . str_pad((string) (DigitalWallet::count() + 1), 6, '0', STR_PAD_LEFT);
    }

    public function nextTransactionNo(): string
    {
        return config('digitalwallet.reference_prefix', 'DWT') . '-' . date('Ymd') . '-' . str_pad((string) (DigitalWalletTransaction::whereDate('created_at', today())->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}
