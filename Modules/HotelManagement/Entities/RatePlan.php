<?php
namespace Modules\HotelManagement\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RatePlan extends Model
{
    use SoftDeletes;
    protected $table = 'hm_rate_plans';
    protected $guarded = ['id'];
}
