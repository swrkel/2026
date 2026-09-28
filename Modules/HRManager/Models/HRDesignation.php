<?php

namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HRDesignation extends Model
{
    protected $table = 'hrm_designations';
    protected $fillable = ['business_id','department_id','name','status'];
}
