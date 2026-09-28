<?php

namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautySaleLine extends Model
{
    protected $guarded = ['id'];
    protected $table = 'bs_sale_lines';
}
