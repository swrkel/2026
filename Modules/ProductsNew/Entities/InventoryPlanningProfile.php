<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class InventoryPlanningProfile extends Model
{
    protected $table = 'products_new_inventory_planning_profiles';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','product_id','variation_id','minimum_qty','maximum_qty','safety_stock_qty','lead_time_days','seasonality_profile','planning_method','reorder_qty','is_active','created_by','updated_by'];
    protected $casts = ['payload'=>'array','seasonality_profile'=>'array','snapshot_date'=>'date','classification_date'=>'date','proposal_date'=>'date','approved_at'=>'datetime','last_sale_at'=>'datetime','last_purchase_at'=>'datetime','is_active'=>'boolean'];
}
