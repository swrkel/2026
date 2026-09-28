<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthVaccineBatch extends Model
{
    protected $table = 'myhealth_vaccine_batches';

    protected $fillable = [
        'business_id','location_id','vaccine_id','batch_no','expiry_date','received_qty','used_qty','available_qty','supplier','purchase_reference','status','created_by','updated_by'
    ];

    protected $casts = ['expiry_date' => 'date'];
}
