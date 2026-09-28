<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringWorkflowHistory extends Model
{
    protected $fillable = ['business_id','tailoring_job_card_id','from_status','to_status','assigned_to','changed_by','changed_at','remarks'];
    protected $casts = ['changed_at'=>'datetime'];
}
