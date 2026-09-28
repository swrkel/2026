<?php

namespace Modules\MyHealthMembers\Entities;

class MyHealthBillingInvoice extends MyHealthBaseModel
{
    protected $table = 'myhealth_billing_invoices';
    protected $guarded = ['id'];

    public function member() { return $this->belongsTo(MyHealthMember::class, 'member_id'); }
    public function items() { return $this->hasMany(MyHealthBillingInvoiceItem::class, 'invoice_id'); }
    public function payments() { return $this->hasMany(MyHealthBillingPayment::class, 'invoice_id'); }
}
