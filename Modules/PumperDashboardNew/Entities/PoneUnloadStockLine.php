<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneUnloadStockLine extends PoneBaseModel
{
    protected $table = 'pone_unload_stock_lines';
    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:6',
        'amount' => 'decimal:4',
        'dip_reading' => 'decimal:6',
        'current_stock' => 'decimal:6',
    ];

    public function unloadStock() { return $this->belongsTo(PoneUnloadStock::class, 'unload_stock_id'); }
}
