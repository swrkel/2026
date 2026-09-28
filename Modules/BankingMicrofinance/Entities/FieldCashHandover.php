<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class FieldCashHandover extends Model
{
    protected $table = 'bkg_mfi_field_cash_handovers';
    protected $guarded = ['id'];
}
