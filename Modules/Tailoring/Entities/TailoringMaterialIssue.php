<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringMaterialIssue extends Model
{
    protected $fillable = ['business_id','tailoring_job_card_id','product_id','material_name','quantity','unit','cost','issued_date','issued_by','remarks'];
    protected $casts = ['issued_date'=>'date'];
}
