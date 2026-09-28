<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneOtherSaleLine extends PoneBaseModel
{
    protected $table = 'pone_other_sale_lines';
    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:6',
        'discount_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'balance_stock_snapshot' => 'decimal:6',
    ];

    public function sale() { return $this->belongsTo(PoneOtherSale::class, 'other_sale_id'); }
}
