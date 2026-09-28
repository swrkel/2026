<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyGiftVoucher extends Model
{
    protected $table = 'bs_gift_vouchers';
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(BeautyVoucherTransaction::class, 'voucher_id');
    }
}
