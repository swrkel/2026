<?php

namespace Modules\BankingTradeFinance\Entities;

use Illuminate\Database\Eloquent\Model;

class TradeDocument extends Model
{
    protected $table = 'bkg_tf_documents';
    protected $guarded = ['id'];
}
