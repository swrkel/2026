<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewDeliveryProof extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_delivery_proofs';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'delivery_id',
    'sales_order_id',
    'customer_id',
    'receiver_name',
    'receiver_mobile',
    'proof_file',
    'gps_lat',
    'gps_lng',
    'received_at',
    'remarks'
    ];
}
