<?php

namespace Modules\Deposits\Models;

use Illuminate\Database\Eloquent\Model;

class DepositSetting extends Model
{
    protected $table = 'deposit_settings';
    protected $guarded = ['id'];
}
