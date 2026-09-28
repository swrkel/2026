<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewBusinessLimit extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_business_limits';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'max_vehicles',
    'max_sales_reps',
    'max_territories',
    'max_routes',
    'max_warehouses',
    'max_delivery_users',
    'max_customer_portal_users',
    'max_active_orders',
    'max_daily_deliveries',
    'updated_by'
    ];
}
