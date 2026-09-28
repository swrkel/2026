<?php

namespace Modules\Subscription\Entities;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvoice extends Model
{
    protected $table = 'subscription_invoices';

    protected $fillable = [
        'invoice_no',
        'business_id',
        'user_id',
        'customer_id',
        'customer_code',
        'customer_address',
        'from_date',
        'to_date',
        'bank_account_id',
        'payment_term_id',
        'banner_id',
        'payment_method',
        'payment_details',
        'total',
    ];

    /* ================= RELATIONS ================= */

    public function items()
    {
        return $this->hasMany(SubscriptionInvoiceItem::class, 'invoice_id');
    }

    public function customer()
    {
        return $this->belongsTo(\App\Contact::class, 'customer_id');
    }

    public function bank()
    {
        return $this->belongsTo(SubscriptionBankAccount::class, 'bank_account_id');
    }

    public function paymentTerm()
    {
        return $this->belongsTo(SubscriptionPaymentTerm::class, 'payment_term_id');
    }

    public function banner()
    {
        return $this->belongsTo(SubscriptionBanner::class, 'banner_id');
    }
}
