<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewCommandCenterSnapshot extends Model
{
    protected $table = 'products_new_command_center_snapshots';
    protected $guarded = ['id'];
    protected $casts = ['summary_payload'=>'array','inventory_payload'=>'array','finance_payload'=>'array','sales_payload'=>'array','purchase_payload'=>'array','alerts_payload'=>'array','integration_payload'=>'array'];
}
