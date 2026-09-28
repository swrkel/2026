<?php
namespace Modules\HRManager\Models;
use Illuminate\Database\Eloquent\Model;
class HrDeductionType extends Model
{
    protected $table = 'hr_deduction_types';
    protected $fillable = ['business_id','code','name','calculation_type','default_amount','deduction_category','statutory','status','created_by'];
}
