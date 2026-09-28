<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseTransaction extends Model
{
    protected $table = 'transactions';

    protected $guarded = ['id'];

    public function scopePurchase($query)
    {
        return $query->whereIn('type', ['purchase', 'purchase_order']);
    }
}
