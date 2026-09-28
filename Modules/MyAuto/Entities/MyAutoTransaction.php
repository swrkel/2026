<?php

namespace Modules\MyAuto\Entities;

use Illuminate\Database\Eloquent\Model;

class MyAutoTransaction extends Model
{
    protected $table = 'my_auto_transactions';

    protected $fillable = [
        'my_auto_daily_log_id',
        'type',
        'amount',
        'expense_field',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function dailyLog()
    {
        return $this->belongsTo(MyAutoDailyLog::class, 'my_auto_daily_log_id');
    }
}
