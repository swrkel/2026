<?php

namespace Modules\BankingInternetBanking\Entities;

use Illuminate\Database\Eloquent\Model;

class InternetBankingBeneficiary extends Model
{
    protected $table = 'bkg_ib_beneficiaries';
    protected $guarded = ['id'];
}
