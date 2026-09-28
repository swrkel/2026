<?php

namespace Modules\Chequer\Entities\Chequer;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ChequerStamp extends Model
{
    use LogsActivity;

    protected $table = 'chequer_stamps';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected static $logAttributes = ['*'];
    protected static $logFillable = true;
    protected static $logName = 'Chequer Stamps';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll();
    }
}
