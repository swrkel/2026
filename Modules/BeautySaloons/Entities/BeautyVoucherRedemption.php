<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyVoucherRedemption extends Model
{
    protected $table = 'bs_voucher_redemptions';
    protected $guarded = ['id'];
}
