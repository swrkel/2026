<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthVaccine extends Model
{
    protected $table = 'myhealth_vaccines';

    protected $fillable = [
        'business_id','location_id','vaccine_code','vaccine_name','manufacturer','vaccine_type','dose_schedule','storage_temperature','default_interval_days','booster_required','booster_interval_days','status','notes','created_by','updated_by'
    ];
}
