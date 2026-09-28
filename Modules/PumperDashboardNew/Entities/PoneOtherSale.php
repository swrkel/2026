<?php

namespace Modules\PumperDashboardNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PoneOtherSale extends PoneBaseModel
{
    use SoftDeletes;
    protected $table = 'pone_other_sales';
    protected $casts = [
        'sale_at' => 'datetime',
        'gross_amount' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'net_amount' => 'decimal:4',
        'edited_at' => 'datetime',
        'voided_at' => 'datetime',
        'last_printed_at' => 'datetime',
    ];
    public function shift() { return $this->belongsTo(PoneShift::class, 'shift_id'); }
    public function lines() { return $this->hasMany(PoneOtherSaleLine::class, 'other_sale_id'); }
}
