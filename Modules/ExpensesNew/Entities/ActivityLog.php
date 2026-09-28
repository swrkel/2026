<?php

namespace Modules\ExpensesNew\Entities;

class ActivityLog extends BaseModel
{
    protected $table = 'expnew_activity_logs';
    protected $guarded = ['id'];
    protected $casts = ['before_json' => 'array', 'after_json' => 'array'];
}
