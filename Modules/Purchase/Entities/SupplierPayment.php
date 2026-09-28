<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $table = 'transaction_payments';
    protected $guarded = ['id'];
}
