<?php

namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HRDepartment extends Model
{
    protected $table = 'hrm_departments';
    protected $fillable = ['business_id','name','status'];
}
