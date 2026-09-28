<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrAllowanceType extends Model
{
    protected $table = 'hr_allowance_types';
    protected $fillable = ['business_id','code','name','calculation_type','default_amount','taxable','epf_applicable','status','created_by'];
}
