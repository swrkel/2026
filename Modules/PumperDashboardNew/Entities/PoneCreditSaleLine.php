<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneCreditSaleLine extends PoneBaseModel
{
    protected $table = 'pone_credit_sale_lines';
    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
    ];

    public function creditSale() { return $this->belongsTo(PoneCreditSale::class, 'credit_sale_id'); }
}
