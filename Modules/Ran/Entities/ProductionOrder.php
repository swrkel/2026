<?php

namespace Modules\Ran\Entities;

class ProductionOrder extends RanModel
{
    protected $table = 'ran_production_orders';
    protected $casts = ['order_date'=>'date','due_date'=>'date','estimated_cost'=>'decimal:4','actual_cost'=>'decimal:4','approved_at'=>'datetime'];
    public function lines(){return $this->hasMany(ProductionOrderLine::class);}
    public function artisan(){return $this->belongsTo(Artisan::class);}
    public function materialIssues(){return $this->hasMany(MaterialIssue::class);}
    public function receipts(){return $this->hasMany(ProductionReceipt::class);}
}
