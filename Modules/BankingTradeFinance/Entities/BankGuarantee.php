<?php

namespace Modules\BankingTradeFinance\Entities;

use Illuminate\Database\Eloquent\Model;

class BankGuarantee extends Model
{
    protected $table = 'bkg_tf_bank_guarantees';
    protected $guarded = ['id'];
}
