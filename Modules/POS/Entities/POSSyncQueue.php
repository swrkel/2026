<?php
namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;

class POSSyncQueue extends Model
{
    protected $table = 'pos_offline_sync_queue';
    protected $guarded = ['id'];
    protected $casts = [
        'payload' => 'array',
        'server_response' => 'array',
        'created_offline_at' => 'datetime',
        'synced_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
