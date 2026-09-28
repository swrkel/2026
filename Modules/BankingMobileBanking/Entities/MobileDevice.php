<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileDevice extends Model
{
    protected $table = 'banking_mobile_devices';
    protected $guarded = ['id'];
}
