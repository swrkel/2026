<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringFitting extends Model
{
    protected $fillable = ['business_id','tailoring_job_card_id','fitting_date','fitting_time','status','customer_comments','tailor_notes','requires_alteration','created_by'];
    protected $casts = ['fitting_date'=>'date','requires_alteration'=>'boolean'];
}
