<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewSplitBillLine extends Model
{
    protected $table = 'rn_split_bill_lines';
    protected $guarded = ['id'];
}
