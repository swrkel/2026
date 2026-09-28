<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewTripLine extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_trip_lines';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'trip_id',
    'loading_id',
    'sales_order_id',
    'customer_id',
    'sequence_no',
    'delivery_address',
    'status',
    'remarks'
    ];
}
