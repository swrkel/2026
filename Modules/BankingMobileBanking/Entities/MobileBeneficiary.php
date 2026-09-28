<?php

namespace Modules\BankingMobileBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class MobileBeneficiary extends Model
{
    protected $table = 'banking_mobile_beneficiaries';
    protected $guarded = ['id'];
}
