<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthBillingInvoiceItem extends MyHealthBaseModel
{
    protected $table = 'myhealth_billing_invoice_items';
    protected $guarded = ['id'];

    public function invoice() { return $this->belongsTo(MyHealthBillingInvoice::class, 'invoice_id'); }
    public function service() { return $this->belongsTo(MyHealthBillingService::class, 'service_id'); }
}
