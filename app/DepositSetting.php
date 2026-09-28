<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepositSetting extends Model
{
    protected $table = 'deposit_settings';
    
    protected $fillable = [
        'business_id',
        'settings_key',
        'settings_value'
    ];
}
