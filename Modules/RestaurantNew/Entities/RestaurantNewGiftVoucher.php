<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewGiftVoucher extends Model
{
    protected $table = 'rn_gift_vouchers';
    protected $guarded = ['id'];

    public function transactions()
    {
        return $this->hasMany(RestaurantNewGiftVoucherTransaction::class, 'gift_voucher_id');
    }
}
