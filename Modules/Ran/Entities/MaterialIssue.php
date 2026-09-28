<?php

namespace Modules\Ran\Entities;

class MaterialIssue extends RanModel
{
    protected $table = 'ran_material_issues';
    protected $casts = ['issue_date'=>'date'];
    public function lines(){return $this->hasMany(MaterialIssueLine::class);}
    public function productionOrder(){return $this->belongsTo(ProductionOrder::class);}
}
