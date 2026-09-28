<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementUnloadStockLine extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_unload_stock_lines';
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'amount' => 'decimal:4',
        'dip_reading' => 'decimal:3',
        'current_stock' => 'decimal:3',
    ];

    public function unloadStock()
    {
        return $this->belongsTo(PdnewSettlementUnloadStock::class, 'unload_stock_id');
    }
}
