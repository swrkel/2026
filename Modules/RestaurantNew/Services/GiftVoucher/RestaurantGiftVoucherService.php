<?php

namespace Modules\RestaurantNew\Services\GiftVoucher;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewGiftVoucher;
use Modules\RestaurantNew\Entities\RestaurantNewGiftVoucherTransaction;

class RestaurantGiftVoucherService
{
    public function list(array $filters = [])
    {
        return RestaurantNewGiftVoucher::query()
            ->when($filters['business_id'] ?? null, fn ($q, $v) => $q->where('business_id', $v))
            ->when($filters['business_location_id'] ?? null, fn ($q, $v) => $q->where('business_location_id', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->latest('id');
    }

    public function issue(array $data): RestaurantNewGiftVoucher
    {
        return DB::transaction(function () use ($data) {
            $amount = (float) ($data['issue_amount'] ?? 0);
            $voucher = RestaurantNewGiftVoucher::create(array_merge($data, [
                'balance_amount' => $amount,
                'status' => $data['status'] ?? 'active',
            ]));

            $this->recordTransaction($voucher, 'issue', $amount, $amount, 'Voucher issued');
            return $voucher;
        });
    }

    public function redeem(RestaurantNewGiftVoucher $voucher, float $amount, array $context = []): RestaurantNewGiftVoucher
    {
        return DB::transaction(function () use ($voucher, $amount, $context) {
            $amount = max(0, $amount);
            if ($amount > (float) $voucher->balance_amount) {
                throw new \InvalidArgumentException('Redeem amount exceeds available voucher balance.');
            }

            $voucher->balance_amount = (float) $voucher->balance_amount - $amount;
            $voucher->status = $voucher->balance_amount <= 0 ? 'redeemed' : 'active';
            $voucher->save();

            $this->recordTransaction($voucher, 'redeem', -$amount, (float) $voucher->balance_amount, $context['note'] ?? 'Voucher redeemed', $context);
            return $voucher->refresh();
        });
    }

    public function topUp(RestaurantNewGiftVoucher $voucher, float $amount, array $context = []): RestaurantNewGiftVoucher
    {
        return DB::transaction(function () use ($voucher, $amount, $context) {
            $amount = max(0, $amount);
            $voucher->balance_amount = (float) $voucher->balance_amount + $amount;
            $voucher->status = 'active';
            $voucher->save();

            $this->recordTransaction($voucher, 'top_up', $amount, (float) $voucher->balance_amount, $context['note'] ?? 'Voucher topped up', $context);
            return $voucher->refresh();
        });
    }

    protected function recordTransaction(RestaurantNewGiftVoucher $voucher, string $type, float $amount, float $balanceAfter, string $note, array $context = []): void
    {
        RestaurantNewGiftVoucherTransaction::create([
            'business_id' => $voucher->business_id,
            'business_location_id' => $voucher->business_location_id,
            'gift_voucher_id' => $voucher->id,
            'transaction_type' => $type,
            'reference_type' => $context['reference_type'] ?? null,
            'reference_id' => $context['reference_id'] ?? null,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'note' => $note,
            'created_by' => $context['created_by'] ?? null,
        ]);
    }
}
