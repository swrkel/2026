<?php

namespace Modules\DigitalWallet\Services\Financial;

use Illuminate\Support\Facades\DB;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletAdjustment;
use Modules\DigitalWallet\Entities\DigitalWalletReservation;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;
use Modules\DigitalWallet\Services\Ledger\DigitalWalletLedgerService;
use Modules\DigitalWallet\Services\Rules\DigitalWalletRuleService;
use RuntimeException;

class DigitalWalletFinancialEngineService
{
    public function __construct(
        protected DigitalWalletLedgerService $ledgerService,
        protected DigitalWalletRuleService $ruleService
    ) {}

    public function recharge(DigitalWallet $wallet, float $amount, array $meta = []): DigitalWalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $meta) {
            $before = (float) $wallet->available_balance;
            $after = $before + $amount;
            $wallet->update([
                'available_balance' => $after,
                'total_balance' => $after + (float) $wallet->reserved_balance,
            ]);

            $transaction = $this->createTransaction($wallet, 'recharge', $amount, $before, $after, 'completed', $meta);
            $this->ledgerService->record($wallet->refresh(), $transaction, 'recharge_credit', 0, $amount, 'Wallet recharge', $meta);

            return $transaction;
        });
    }

    public function reserve(DigitalWallet $wallet, float $amount, array $meta = []): DigitalWalletReservation
    {
        $check = $this->ruleService->canCharge($wallet, $amount);
        if (! $check['allowed']) {
            throw new RuntimeException($check['message']);
        }

        return DB::transaction(function () use ($wallet, $amount, $meta) {
            $before = (float) $wallet->available_balance;
            $availableAfter = $before - $amount;
            $reservedAfter = (float) $wallet->reserved_balance + $amount;

            $wallet->update([
                'available_balance' => $availableAfter,
                'reserved_balance' => $reservedAfter,
                'total_balance' => $availableAfter + $reservedAfter,
            ]);

            $transaction = $this->createTransaction($wallet, 'reservation', $amount, $before, $availableAfter, 'reserved', $meta);
            $reservation = DigitalWalletReservation::create([
                'reservation_no' => $meta['reservation_no'] ?? $this->nextNo('DWR', DigitalWalletReservation::class, 'reservation_no'),
                'wallet_id' => $wallet->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'source_module' => $meta['source_module'] ?? null,
                'source_reference' => $meta['source_reference'] ?? null,
                'status' => 'reserved',
                'expires_at' => $meta['expires_at'] ?? now()->addMinutes((int) config('digitalwallet.reservation_expiry_minutes', 30)),
                'business_id' => $wallet->business_id,
                'location_id' => $wallet->location_id,
                'created_by' => auth()->id(),
                'meta' => $meta,
            ]);

            $this->ledgerService->record($wallet->refresh(), $transaction, 'reserve', $amount, 0, 'Wallet reservation', $meta);

            return $reservation;
        });
    }

    public function commitReservation(DigitalWalletReservation $reservation, array $meta = []): DigitalWalletTransaction
    {
        if ($reservation->status !== 'reserved') {
            throw new RuntimeException('Only reserved reservations can be committed.');
        }

        return DB::transaction(function () use ($reservation, $meta) {
            $wallet = DigitalWallet::lockForUpdate()->findOrFail($reservation->wallet_id);
            $before = (float) $wallet->available_balance;
            $reservedAfter = max(0, (float) $wallet->reserved_balance - (float) $reservation->amount);

            $wallet->update([
                'reserved_balance' => $reservedAfter,
                'total_balance' => (float) $wallet->available_balance + $reservedAfter,
            ]);

            $reservation->update([
                'status' => 'committed',
                'committed_at' => now(),
                'meta' => array_merge($reservation->meta ?? [], $meta),
            ]);

            $transaction = $this->createTransaction($wallet, 'commit_reservation', (float) $reservation->amount, $before, $before, 'completed', array_merge($reservation->meta ?? [], $meta));
            $this->ledgerService->record($wallet->refresh(), $transaction, 'commit_reservation', (float) $reservation->amount, 0, 'Reservation committed', $meta);

            return $transaction;
        });
    }

    public function releaseReservation(DigitalWalletReservation $reservation, array $meta = []): DigitalWalletTransaction
    {
        if ($reservation->status !== 'reserved') {
            throw new RuntimeException('Only reserved reservations can be released.');
        }

        return DB::transaction(function () use ($reservation, $meta) {
            $wallet = DigitalWallet::lockForUpdate()->findOrFail($reservation->wallet_id);
            $before = (float) $wallet->available_balance;
            $availableAfter = $before + (float) $reservation->amount;
            $reservedAfter = max(0, (float) $wallet->reserved_balance - (float) $reservation->amount);

            $wallet->update([
                'available_balance' => $availableAfter,
                'reserved_balance' => $reservedAfter,
                'total_balance' => $availableAfter + $reservedAfter,
            ]);

            $reservation->update([
                'status' => 'released',
                'released_at' => now(),
                'meta' => array_merge($reservation->meta ?? [], $meta),
            ]);

            $transaction = $this->createTransaction($wallet, 'release_reservation', (float) $reservation->amount, $before, $availableAfter, 'completed', array_merge($reservation->meta ?? [], $meta));
            $this->ledgerService->record($wallet->refresh(), $transaction, 'release_reservation', 0, (float) $reservation->amount, 'Reservation released', $meta);

            return $transaction;
        });
    }

    public function adjust(DigitalWallet $wallet, string $type, float $amount, array $meta = []): DigitalWalletAdjustment
    {
        return DB::transaction(function () use ($wallet, $type, $amount, $meta) {
            $before = (float) $wallet->available_balance;
            $after = $type === 'debit' ? $before - $amount : $before + $amount;
            if ($after < 0) {
                throw new RuntimeException('Adjustment would make wallet balance negative.');
            }

            $wallet->update([
                'available_balance' => $after,
                'total_balance' => $after + (float) $wallet->reserved_balance,
            ]);

            $adjustment = DigitalWalletAdjustment::create([
                'adjustment_no' => $meta['adjustment_no'] ?? $this->nextNo('DWA', DigitalWalletAdjustment::class, 'adjustment_no'),
                'wallet_id' => $wallet->id,
                'adjustment_type' => $type,
                'amount' => $amount,
                'currency' => $wallet->currency,
                'reason' => $meta['reason'] ?? null,
                'status' => 'completed',
                'business_id' => $wallet->business_id,
                'location_id' => $wallet->location_id,
                'created_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'meta' => $meta,
            ]);

            $transaction = $this->createTransaction($wallet, $type . '_adjustment', $amount, $before, $after, 'completed', $meta);
            $this->ledgerService->record($wallet->refresh(), $transaction, $type . '_adjustment', $type === 'debit' ? $amount : 0, $type === 'credit' ? $amount : 0, 'Wallet adjustment', $meta);

            return $adjustment;
        });
    }

    protected function createTransaction(DigitalWallet $wallet, string $type, float $amount, float $before, float $after, string $status, array $meta): DigitalWalletTransaction
    {
        return DigitalWalletTransaction::create([
            'transaction_no' => $meta['transaction_no'] ?? $this->nextNo('DWT', DigitalWalletTransaction::class, 'transaction_no'),
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
            'status' => $status,
            'note' => $meta['note'] ?? null,
            'meta' => $meta,
        ]);
    }

    protected function nextNo(string $prefix, string $modelClass, string $column): string
    {
        $count = $modelClass::whereDate('created_at', today())->count() + 1;
        return $prefix . '-' . date('Ymd') . '-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
    }
}
