<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewGiftVoucherTransaction extends Model
{
    protected $table = 'rn_gift_voucher_transactions';
    protected $guarded = ['id'];

    public function voucher()
    {
        return $this->belongsTo(RestaurantNewGiftVoucher::class, 'gift_voucher_id');
    }
}
