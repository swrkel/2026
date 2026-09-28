<?php

namespace Modules\PumperDashboardNew\Entities;

class PonePaymentEditHistory extends PoneBaseModel
{
    protected $table = 'pone_payment_edit_histories';
    protected $casts = ['before_data' => 'array', 'after_data' => 'array', 'edited_at' => 'datetime'];
    public function payment() { return $this->belongsTo(PonePayment::class, 'payment_id'); }
}
