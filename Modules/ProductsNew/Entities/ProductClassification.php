<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductClassification extends Model
{
    protected $table = 'products_new_product_classifications';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','product_id','abc_class','xyz_class','combined_class','annual_consumption_value','demand_variability','movement_rank','classification_date','payload','created_by'];
    protected $casts = ['payload'=>'array','seasonality_profile'=>'array','snapshot_date'=>'date','classification_date'=>'date','proposal_date'=>'date','approved_at'=>'datetime','last_sale_at'=>'datetime','last_purchase_at'=>'datetime','is_active'=>'boolean'];
}
