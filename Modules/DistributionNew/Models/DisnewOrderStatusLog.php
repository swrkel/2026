<?php
namespace Modules\DistributionNew\Models;
use Illuminate\Database\Eloquent\Model;
class DisnewOrderStatusLog extends Model
{
    public $timestamps = false;
    protected $table = 'disnew_order_status_logs';
    protected $fillable = ['business_id','sales_order_id','from_status','to_status','changed_by_type','changed_by','note','created_at'];
}
