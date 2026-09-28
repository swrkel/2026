<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewMobileDevice extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_mobile_devices';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'user_id',
        'sales_rep_id',
        'device_uid',
        'device_name',
        'platform',
        'app_version',
        'last_sync_at',
        'is_active',
        'registered_at'
    ];
}
