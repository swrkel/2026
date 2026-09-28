<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewSyncConflict extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_sync_conflicts';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
        'device_id',
        'user_id',
        'entity_type',
        'entity_local_id',
        'entity_server_id',
        'server_payload_json',
        'client_payload_json',
        'resolution_status',
        'resolved_by',
        'resolved_at'
    ];
}
