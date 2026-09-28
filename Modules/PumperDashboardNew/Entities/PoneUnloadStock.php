<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PoneUnloadStock extends PoneBaseModel
{
    use SoftDeletes;
    protected $table = 'pone_unload_stocks';
    protected $casts = [
        'unloaded_at' => 'datetime',
        'total_quantity' => 'decimal:6',
        'total_amount' => 'decimal:4',
        'edited_at' => 'datetime',
        'voided_at' => 'datetime',
        'last_printed_at' => 'datetime',
    ];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function lines() { return $this->hasMany(PoneUnloadStockLine::class, 'unload_stock_id'); }
}
