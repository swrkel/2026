<?php

namespace Modules\SMS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmsGroup extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'SMS Group';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
/*
    public function user()
    {
        return $this->belongsTo(\App\User::class, 'created_by', 'id');
    }
*/
    public function user()
    {
    return $this->belongsTo(\App\User::class, 'created_by', 'id')
                ->select(['id','username','first_name','last_name']);
    }

 


    protected $casts = [

        'numbers' => 'array'
    ];
}
