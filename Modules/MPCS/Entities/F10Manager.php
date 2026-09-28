<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class F10Manager extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'F10 Manager';
    protected $table = 'mpcs_f10_managers';
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['fillable']);
    }
}
