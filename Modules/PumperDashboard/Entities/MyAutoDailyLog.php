<?php

namespace Modules\MyAuto\Entities;

use Illuminate\Database\Eloquent\Model;

class MyAutoDailyLog extends Model
{
    protected $table = 'my_auto_daily_logs';
    

    protected $fillable = [
        'business_id',
        'my_auto_setting_id',
        'log_date',
        'starting_meter',
        'current_meter',
        'trip_income',
        'expense_1',
        'expense_2',
        'expense_3',
        'expense_4',
        'expense_5'
    ];

    public function myAutoSetting()
    {
        return $this->belongsTo(MyAutoSetting::class, 'my_auto_setting_id');
    }

    public function transactions()
    {
        return $this->hasMany(MyAutoTransaction::class, 'my_auto_daily_log_id');
    }
}


