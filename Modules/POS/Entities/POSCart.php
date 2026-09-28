<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POSCart extends Model
{
    use SoftDeletes;

    protected $table = 'pos_carts';
    protected $guarded = ['id'];
}
