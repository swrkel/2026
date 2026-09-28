<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyGiftVoucher;

class VoucherRedemptionService
{
    public function validateVoucher(BeautyGiftVoucher $voucher): bool
    {
        if (in_array($voucher->status, ['cancelled', 'expired', 'redeemed'], true)) {
            return false;
        }
        if (!empty($voucher->expiry_date) && $voucher->expiry_date < date('Y-m-d')) {
            return false;
        }
        return ((float) $voucher->balance_amount) > 0;
    }
}
