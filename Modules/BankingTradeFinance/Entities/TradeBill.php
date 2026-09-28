<?php

namespace Modules\BankingTradeFinance\Entities;

use Illuminate\Database\Eloquent\Model;

class TradeBill extends Model
{
    protected $table = 'bkg_tf_bills';
    protected $guarded = ['id'];
}
