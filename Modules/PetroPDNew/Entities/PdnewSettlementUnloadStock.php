<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSettlementUnloadStock extends PdnewBaseModel
{
    protected $table = 'pdnew_settlement_unload_stocks';
    protected $casts = [
        'unloaded_at' => 'datetime',
        'total_quantity' => 'decimal:3',
        'total_amount' => 'decimal:4',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }

    public function lines()
    {
        return $this->hasMany(PdnewSettlementUnloadStockLine::class, 'unload_stock_id');
    }
}
