<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductCostSnapshot extends Model
{
    protected $table = 'products_new_cost_snapshots';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','product_id','variation_id','snapshot_date','last_cost','average_cost','moving_average_cost','standard_cost','replacement_cost','landed_cost','margin_percentage','payload','created_by'];
    protected $casts = ['payload'=>'array','seasonality_profile'=>'array','snapshot_date'=>'date','classification_date'=>'date','proposal_date'=>'date','approved_at'=>'datetime','last_sale_at'=>'datetime','last_purchase_at'=>'datetime','is_active'=>'boolean'];
}
