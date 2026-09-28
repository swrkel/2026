<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthBackupRecord extends Model
{
    protected $table = 'myhealth_backup_records';

    protected $fillable = [
        'business_id', 'backup_no', 'backup_type', 'backup_scope', 'status',
        'storage_disk', 'storage_path', 'file_size', 'started_at', 'completed_at',
        'created_by', 'notes', 'metadata'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
