<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewUnloadStockLine extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_unload_stock_lines';

    public function unloadStock() { return $this->belongsTo(PdirectnewUnloadStock::class, 'unload_stock_id'); }
    public function tank() { return $this->belongsTo(PdirectnewTank::class, 'tank_id'); }
}
