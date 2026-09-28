<?php

namespace Modules\BankingMicrofinance\Entities;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'bkg_mfi_settings';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','key','value'];
}
