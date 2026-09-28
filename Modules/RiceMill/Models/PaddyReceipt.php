<?php
namespace Modules\RiceMill\Models;

class PaddyReceipt extends BaseRiceMillModel
{
    protected $table = 'rcm_paddy_receipts';
    protected $casts = [
        'received_at'=>'datetime',
        'moisture_percent'=>'decimal:3',
        'foreign_matter_percent'=>'decimal:3',
        'foreign_matter_limit_percent'=>'decimal:3',
    ];

    public function paddyLot(){ return $this->belongsTo(PaddyLot::class,'paddy_lot_id'); }
    public function variety(){ return $this->belongsTo(PaddyVariety::class,'paddy_variety_id'); }
    public function weighbridgeEntry(){ return $this->belongsTo(WeighbridgeEntry::class,'weighbridge_entry_id'); }
}
