<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewWarehouseTransfer extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_warehouse_transfers';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'transfer_no',
    'from_warehouse_id',
    'to_vehicle_id',
    'to_warehouse_id',
    'status',
    'transfer_date',
    'approved_by',
    'created_by',
    'remarks'
    ];
}
