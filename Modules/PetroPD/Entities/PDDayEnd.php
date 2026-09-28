<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PDDayEnd extends Model
{
    use LogsActivity;

    protected $table = 'day_ends';
    protected $guarded = ['id'];
    protected static $logAttributes = ['*'];
    protected static $logFillable = true;
    protected static $logName = 'PetroPD Day End';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }
}
