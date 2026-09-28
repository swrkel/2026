<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockIntelligenceSnapshot extends Model
{
    protected $table = 'products_new_stock_intelligence_snapshots';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','product_id','variation_id','snapshot_date','current_stock','available_stock','reserved_stock','on_order_qty','in_transit_qty','stock_age_days','last_sale_at','last_purchase_at','movement_status','recommended_action','risk_level','payload','created_by'];
    protected $casts = ['payload'=>'array','seasonality_profile'=>'array','snapshot_date'=>'date','classification_date'=>'date','proposal_date'=>'date','approved_at'=>'datetime','last_sale_at'=>'datetime','last_purchase_at'=>'datetime','is_active'=>'boolean'];
}
