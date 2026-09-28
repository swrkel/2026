<?php

namespace Modules\PetroDirectNew\Entities;

class PdirectnewUnloadStock extends PdirectnewBaseModel
{
    protected $table = 'pdirectnew_unload_stocks';
    protected $casts = ['unload_date' => 'date'];

    public function lines() { return $this->hasMany(PdirectnewUnloadStockLine::class, 'unload_stock_id'); }
    public function operator() { return $this->belongsTo(PdirectnewOperator::class, 'operator_id'); }
    public function shift() { return $this->belongsTo(PdirectnewShift::class, 'shift_id'); }
}
