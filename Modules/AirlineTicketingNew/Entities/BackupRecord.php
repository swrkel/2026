<?php
namespace Modules\AirlineTicketingNew\Entities;

class BackupRecord extends BaseAirlineTicketingModel
{
    protected $table = 'atn_backup_records';
    protected $guarded = ['id'];
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata_json' => 'array',
    ];
}
