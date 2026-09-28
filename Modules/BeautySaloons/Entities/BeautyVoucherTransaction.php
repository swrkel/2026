<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyVoucherTransaction extends Model
{
    protected $table = 'bs_voucher_transactions';
    protected $guarded = ['id'];
}
