<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewOfflineQueue extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_offline_queues';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'location_id',
        'user_id',
        'device_id',
        'entity_type',
        'entity_local_id',
        'entity_server_id',
        'payload_json',
        'sync_status',
        'error_message',
        'synced_at'
    ];
}
