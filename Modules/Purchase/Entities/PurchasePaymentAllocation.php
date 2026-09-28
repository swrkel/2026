<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchasePaymentAllocation extends Model
{
    protected $table = 'purchase_payment_allocations';
    protected $guarded = ['id'];
}
