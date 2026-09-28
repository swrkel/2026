<?php

namespace Modules\MyAuto\Entities;

use Illuminate\Database\Eloquent\Model;

class MyAutoSetting extends Model
{
    protected $table = 'my_auto_settings';

    protected $fillable = [
        'user_id',
        'business_id',
        'user_name',
        'first_date',
        'starting_meter',
        'auto_number',
        'is_locked',
        'passcode',
        'sms_mobile_numbers',
        'is_sms_enabled'
    ];

    public function logs()
    {
        return $this->hasMany(MyAutoDailyLog::class);
    }
}
