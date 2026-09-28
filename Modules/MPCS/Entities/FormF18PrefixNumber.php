<?php

namespace Modules\MPCS\Entities;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FormF18PrefixNumber extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Form F18 Prefix & Numbers';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'opening_date' => 'date',
        'transferred_locations' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    /**
     * User who created this Prefix & Numbers row.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

