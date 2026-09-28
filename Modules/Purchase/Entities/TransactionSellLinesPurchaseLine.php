<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionSellLinesPurchaseLine extends Model
{
    protected $table = 'transaction_sell_lines_purchase_lines';

    protected $guarded = ['id'];
}
