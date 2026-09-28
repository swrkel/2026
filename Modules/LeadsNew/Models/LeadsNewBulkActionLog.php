<?php

namespace Modules\LeadsNew\Models;

use Illuminate\Database\Eloquent\Model;

class LeadsNewBulkActionLog extends Model
{
    protected $table = 'leads_new_bulk_action_logs';

    protected $guarded = ['id'];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];
}
