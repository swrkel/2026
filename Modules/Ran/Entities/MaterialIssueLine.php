<?php

namespace Modules\Ran\Entities;

class MaterialIssueLine extends RanModel
{
    protected $table = 'ran_material_issue_lines';
    protected $casts = ['quantity'=>'decimal:4','gross_weight'=>'decimal:3','net_weight'=>'decimal:3','fine_weight'=>'decimal:3','unit_cost'=>'decimal:4','total_cost'=>'decimal:4'];
    public function issue(){return $this->belongsTo(MaterialIssue::class,'material_issue_id');}
    public function stockLot(){return $this->belongsTo(StockLot::class);}
    public function item(){return $this->belongsTo(Item::class);}
}
