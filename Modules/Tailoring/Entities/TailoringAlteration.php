<?php

namespace Modules\Tailoring\Entities;

use Illuminate\Database\Eloquent\Model;

class TailoringAlteration extends Model
{
    protected $fillable = ['business_id','tailoring_job_card_id','tailoring_fitting_id','alteration_type','description','status','charge_amount','due_date','assigned_to'];
    protected $casts = ['due_date'=>'date'];
}
