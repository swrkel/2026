<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseLine extends Model
{
    protected $table = 'purchase_lines';
    protected $guarded = ['id'];
}
