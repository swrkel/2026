<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POSCartLine extends Model
{
    use SoftDeletes;

    protected $table = 'pos_cart_lines';
    protected $guarded = ['id'];
}
