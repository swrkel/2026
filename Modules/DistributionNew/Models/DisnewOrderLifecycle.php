<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewOrderLifecycle extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_order_lifecycles';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'location_id',
    'sales_order_id',
    'from_status',
    'to_status',
    'changed_by',
    'changed_at',
    'remarks'
    ];
}
