<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ReorderProposal extends Model
{
    protected $table = 'products_new_reorder_proposals';
    protected $guarded = ['id'];
    protected $fillable = ['business_id','location_id','product_id','variation_id','proposal_date','current_stock','minimum_qty','maximum_qty','safety_stock_qty','lead_time_days','recommended_qty','estimated_cost','status','notes','created_by','approved_by','approved_at'];
    protected $casts = ['payload'=>'array','seasonality_profile'=>'array','snapshot_date'=>'date','classification_date'=>'date','proposal_date'=>'date','approved_at'=>'datetime','last_sale_at'=>'datetime','last_purchase_at'=>'datetime','is_active'=>'boolean'];
}
