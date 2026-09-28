<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnAccount extends Model
{
    protected $table = 'purchase_return_accounts';
    protected $guarded = ['id'];
}
