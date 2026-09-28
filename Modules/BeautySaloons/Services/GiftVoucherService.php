<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyGiftVoucher;
use Modules\BeautySaloons\Entities\BeautyVoucherTransaction;

class GiftVoucherService
{
    public function create(array $data): BeautyGiftVoucher
    {
        return DB::transaction(function () use ($data) {
            $data['balance_amount'] = $data['balance_amount'] ?? $data['original_amount'];
            $data['status'] = $data['status'] ?? 'active';
            return BeautyGiftVoucher::create($data);
        });
    }

    public function sell(BeautyGiftVoucher $voucher, array $data): BeautyVoucherTransaction
    {
        return DB::transaction(function () use ($voucher, $data) {
            $voucher->update(['status' => 'sold', 'sold_at' => now()]);
            return BeautyVoucherTransaction::create(array_merge($data, [
                'voucher_id' => $voucher->id,
                'transaction_type' => 'sale',
                'amount' => $voucher->original_amount,
            ]));
        });
    }

    public function redeem(BeautyGiftVoucher $voucher, float $amount, array $data = []): BeautyVoucherTransaction
    {
        return DB::transaction(function () use ($voucher, $amount, $data) {
            if ($amount <= 0 || $amount > (float) $voucher->balance_amount) {
                throw new \InvalidArgumentException('Invalid redemption amount.');
            }
            $balance = round(((float) $voucher->balance_amount) - $amount, 4);
            $voucher->update(['balance_amount' => $balance, 'status' => $balance <= 0 ? 'redeemed' : 'partially_redeemed']);
            return BeautyVoucherTransaction::create(array_merge($data, [
                'voucher_id' => $voucher->id,
                'transaction_type' => 'redemption',
                'amount' => $amount,
                'balance_after' => $balance,
            ]));
        });
    }
}
