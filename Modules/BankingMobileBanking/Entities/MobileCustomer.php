<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileCustomer extends Model
{
    protected $table = 'banking_mobile_customers';
    protected $guarded = ['id'];
}
