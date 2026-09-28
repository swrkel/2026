<?php
namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;

class POSSyncConflict extends Model
{
    protected $table = 'pos_offline_sync_conflicts';
    protected $guarded = ['id'];
    protected $casts = [
        'payload' => 'array',
        'server_snapshot' => 'array',
        'resolved_at' => 'datetime',
    ];
}
