<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class F10OpeningNumber extends Model
{
    use LogsActivity;
    protected static $logAttributes = ['*'];
    protected static $logName = 'F10 Opening Numbers';
    protected $table = 'mpcs_f10_opening_numbers';
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['fillable']);
    }
}
