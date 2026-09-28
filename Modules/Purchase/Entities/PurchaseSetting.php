<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseSetting extends Model
{
    protected $table = 'purchase_settings';
    protected $guarded = ['id'];
}
