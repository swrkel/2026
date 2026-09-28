<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthBillingPayment extends MyHealthBaseModel
{
    protected $table = 'myhealth_billing_payments';
    protected $guarded = ['id'];

    public function invoice() { return $this->belongsTo(MyHealthBillingInvoice::class, 'invoice_id'); }
}
