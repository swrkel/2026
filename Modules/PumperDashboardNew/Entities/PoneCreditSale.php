<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneCreditSale extends PoneBaseModel
{
    protected $table = 'pone_credit_sales';
    protected $casts = [
        'order_date' => 'date',
        'due_date' => 'date',
        'customer_confirmed' => 'boolean',
        'order_confirmed' => 'boolean',
        'vehicle_confirmed' => 'boolean',
        'customer_confirmed_at' => 'datetime',
        'order_confirmed_at' => 'datetime',
        'vehicle_confirmed_at' => 'datetime',
        'locked_at' => 'datetime',
        'last_printed_at' => 'datetime',
    ];
    public function payment() { return $this->belongsTo(PonePayment::class, 'payment_id'); }
    public function lines() { return $this->hasMany(PoneCreditSaleLine::class, 'credit_sale_id'); }
}
