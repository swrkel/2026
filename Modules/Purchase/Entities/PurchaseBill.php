<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseBill extends Model
{
    protected $table = 'transactions';
    protected $guarded = ['id'];
}
