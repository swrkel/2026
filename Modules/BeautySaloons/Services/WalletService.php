<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyWallet;
use Modules\BeautySaloons\Entities\BeautyWalletTransaction;
use Modules\BeautySaloons\Utils\WalletNumberUtil;

class WalletService
{
    public function indexData(): array
    {
        return ['wallets' => BeautyWallet::latest()->paginate(25)];
    }

    public function createWallet(array $data): BeautyWallet
    {
        return DB::transaction(function () use ($data) {
            $wallet = BeautyWallet::create([
                'business_id' => $data['business_id'] ?? auth()->user()->business_id ?? null,
                'business_location_id' => $data['business_location_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_mobile' => $data['customer_mobile'] ?? null,
                'wallet_no' => $data['wallet_no'] ?? null,
                'opening_balance' => $this->num($data['opening_balance'] ?? 0),
                'balance' => $this->num($data['opening_balance'] ?? 0),
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            if (empty($wallet->wallet_no)) {
                $wallet->wallet_no = WalletNumberUtil::walletNo($wallet->id);
                $wallet->save();
            }

            if ($wallet->opening_balance > 0) {
                $this->createTransaction($wallet, 'opening', $wallet->opening_balance, 'credit', 'Opening wallet balance');
            }

            return $wallet;
        });
    }

    public function topUp(int $walletId, array $data): BeautyWalletTransaction
    {
        return DB::transaction(function () use ($walletId, $data) {
            $wallet = BeautyWallet::lockForUpdate()->findOrFail($walletId);
            $amount = $this->num($data['amount'] ?? 0);
            $wallet->balance += $amount;
            $wallet->save();
            return $this->createTransaction($wallet, 'top_up', $amount, 'credit', $data['note'] ?? 'Wallet top-up', $data);
        });
    }

    public function debit(int $walletId, array $data): BeautyWalletTransaction
    {
        return DB::transaction(function () use ($walletId, $data) {
            $wallet = BeautyWallet::lockForUpdate()->findOrFail($walletId);
            $amount = $this->num($data['amount'] ?? 0);
            if ($wallet->balance < $amount) {
                throw new \RuntimeException('Insufficient wallet balance.');
            }
            $wallet->balance -= $amount;
            $wallet->save();
            return $this->createTransaction($wallet, 'usage', $amount, 'debit', $data['note'] ?? 'Wallet usage', $data);
        });
    }

    public function refund(int $walletId, array $data): BeautyWalletTransaction
    {
        return DB::transaction(function () use ($walletId, $data) {
            $wallet = BeautyWallet::lockForUpdate()->findOrFail($walletId);
            $amount = $this->num($data['amount'] ?? 0);
            $wallet->balance += $amount;
            $wallet->save();
            return $this->createTransaction($wallet, 'refund', $amount, 'credit', $data['note'] ?? 'Wallet refund', $data);
        });
    }

    public function statementData(int $walletId): array
    {
        $wallet = BeautyWallet::findOrFail($walletId);
        return [
            'wallet' => $wallet,
            'transactions' => BeautyWalletTransaction::where('wallet_id', $walletId)->latest()->paginate(50),
        ];
    }

    private function createTransaction(BeautyWallet $wallet, string $type, float $amount, string $direction, string $note = null, array $data = []): BeautyWalletTransaction
    {
        $transaction = BeautyWalletTransaction::create([
            'wallet_id' => $wallet->id,
            'business_id' => $wallet->business_id,
            'business_location_id' => $wallet->business_location_id,
            'transaction_no' => null,
            'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            'transaction_type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'payment_method' => $data['payment_method'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'note' => $note,
            'created_by' => auth()->id(),
        ]);
        $transaction->transaction_no = WalletNumberUtil::transactionNo($transaction->id);
        $transaction->save();
        return $transaction;
    }

    private function num($value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }
}
