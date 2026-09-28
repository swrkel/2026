<?php

namespace Modules\MyHealthMembers\Entities;

use Illuminate\Database\Eloquent\Model;

class MyHealthRestoreRecord extends Model
{
    protected $table = 'myhealth_restore_records';

    protected $fillable = [
        'business_id', 'restore_no', 'backup_record_id', 'restore_scope', 'status',
        'validated_at', 'started_at', 'completed_at', 'requested_by', 'approved_by',
        'notes', 'metadata'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function backup()
    {
        return $this->belongsTo(MyHealthBackupRecord::class, 'backup_record_id');
    }
}
