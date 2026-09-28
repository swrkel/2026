<?php

namespace Modules\PriceChangeNew\Entities;

use Illuminate\Database\Eloquent\Model;

class PriceChangeSetting extends Model
{
    protected $table = 'pcn_price_change_settings';
    protected $guarded = ['id'];
}
