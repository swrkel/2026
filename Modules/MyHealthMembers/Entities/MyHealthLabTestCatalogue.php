<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthLabTestCatalogue extends Model
{
    protected $table = 'myhealth_lab_test_catalogue';

    protected $fillable = [
        'business_id', 'location_id', 'test_code', 'test_name', 'department', 'category',
        'sample_type', 'normal_range', 'turnaround_time', 'price', 'instructions',
        'status', 'created_by', 'updated_by',
    ];
}
