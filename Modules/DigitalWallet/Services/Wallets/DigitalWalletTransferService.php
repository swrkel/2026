<?php

namespace Modules\DigitalWallet\Services\Wallets;

use Illuminate\Support\Facades\DB;
use Modules\DigitalWallet\Entities\DigitalWallet;
use Modules\DigitalWallet\Entities\DigitalWalletTransfer;
use Modules\DigitalWallet\Entities\DigitalWalletTransaction;
use Modules\DigitalWallet\Services\Ledger\DigitalWalletLedgerService;
use Modules\DigitalWallet\Services\Rules\DigitalWalletRuleService;

class DigitalWalletTransferService
{
    public function __construct(
        protected DigitalWalletLedgerService $ledgerService,
        protected DigitalWalletRuleService $ruleService
    ) {}

    public function transfer(DigitalWallet $fromWallet, DigitalWallet $toWallet, float $amount, array $meta = []): DigitalWalletTransfer
    {
        if ($fromWallet->id === $toWallet->id) {
            throw new \RuntimeException('Cannot transfer to the same wallet.');
        }

        if ($fromWallet->currency !== $toWallet->currency) {
            throw new \RuntimeException('Wallet currency mismatch.');
        }

        $check = $this->ruleService->canCharge($fromWallet, $amount);
        if (! $check['allowed']) {
            throw new \RuntimeException($check['message']);
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $meta) {
            $fromBefore = (float) $fromWallet->available_balance;
            $toBefore = (float) $toWallet->available_balance;
            $fromAfter = $fromBefore - $amount;
            $toAfter = $toBefore + $amount;

            $fromWallet->update(['available_balance' => $fromAfter, 'total_balance' => $fromAfter + (float) $fromWallet->reserved_balance]);
            $toWallet->update(['available_balance' => $toAfter, 'total_balance' => $toAfter + (float) $toWallet->reserved_balance]);

            
            $outTransaction = DigitalWalletTransaction::create([
                'transaction_no' => $this->nextTransactionNo('DWTRO'),
                'wallet_id' => $fromWallet->id,
                'transaction_type' => 'transfer_out',
                'amount' => $amount,
                'currency' => $fromWallet->currency,
                'balance_before' => $fromBefore,
                'balance_after' => $fromAfter,
                'source_module' => $meta['source_module'] ?? 'DigitalWallet',
                'source_reference' => $meta['source_reference'] ?? null,
                'status' => 'completed',
                'note' => $meta['note'] ?? null,
                'business_id' => $fromWallet->business_id,
                'location_id' => $fromWallet->location_id,
                'created_by' => auth()->id(),
                'meta' => $meta,
            ]);

            $inTransaction = DigitalWalletTransaction::create([
                'transaction_no' => $this->nextTransactionNo('DWTRI'),
                'wallet_id' => $toWallet->id,
                'transaction_type' => 'transfer_in',
                'amount' => $amount,
                'currency' => $toWallet->currency,
                'balance_before' => $toBefore,
                'balance_after' => $toAfter,
                'source_module' => $meta['source_module'] ?? 'DigitalWallet',
                'source_reference' => $meta['source_reference'] ?? null,
                'status' => 'completed',
                'note' => $meta['note'] ?? null,
                'business_id' => $toWallet->business_id,
                'location_id' => $toWallet->location_id,
                'created_by' => auth()->id(),
                'meta' => $meta,
            ]);

            $transfer = DigitalWalletTransfer::create([
                'transfer_no' => $meta['transfer_no'] ?? $this->nextTransferNo(),
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $amount,
                'currency' => $fromWallet->currency,
                'status' => 'completed',
                'approval_status' => $meta['approval_status'] ?? 'not_required',
                'requested_by' => auth()->id(),
                'approved_by' => $meta['approved_by'] ?? auth()->id(),
                'approved_at' => now(),
                'note' => $meta['note'] ?? null,
                'meta' => array_merge($meta, [
                    'from_balance_before' => $fromBefore,
                    'from_balance_after' => $fromAfter,
                    'to_balance_before' => $toBefore,
                    'to_balance_after' => $toAfter,
                ]),
            ]);

            $this->ledgerService->record($fromWallet->refresh(), $outTransaction, 'transfer_out', $amount, 0, 'Transfer out: '.$transfer->transfer_no, ['transfer_id' => $transfer->id]);
            $this->ledgerService->record($toWallet->refresh(), $inTransaction, 'transfer_in', 0, $amount, 'Transfer in: '.$transfer->transfer_no, ['transfer_id' => $transfer->id]);

            return $transfer;
        });
    }

    public function nextTransferNo(): string
    {
        return config('digitalwallet.transfer_prefix', 'DWTFR') . '-' . date('Ymd') . '-' . str_pad((string) (DigitalWalletTransfer::whereDate('created_at', today())->count() + 1), 6, '0', STR_PAD_LEFT);
    }

    protected function nextTransactionNo(string $prefix): string
    {
        return $prefix . '-' . date('Ymd') . '-' . str_pad((string) (DigitalWalletTransaction::whereDate('created_at', today())->count() + 1), 6, '0', STR_PAD_LEFT);
    }
}

