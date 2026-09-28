<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class FieldOfficer extends Model
{
    protected $table = 'bkg_mfi_field_officers';
    protected $guarded = ['id'];
}
