<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoiceItem extends Model
{
    protected $table = 'subscription_invoice_items';

    protected $fillable = [
        'invoice_id',
        'setting_id',
        'description',
        'cycle',
        'qty',
        'price',
        'total',
        'source', // system / manual
    ];

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'invoice_id');
    }
}
