<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $table = 'transactions';
    protected $guarded = ['id'];
}
