<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileTransfer extends Model
{
    protected $table = 'banking_mobile_transfers';
    protected $guarded = ['id'];
}
