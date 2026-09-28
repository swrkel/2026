<?php
namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementSwDailyShift extends Model
{
    use LogsActivity;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('settlementsw.tables.daily_shifts', 'petro_daily_shifts'));
    }

    protected static $logAttributes = ['*'];
    protected static $logFillable = true;
    protected static $logName = 'Settlement SW Daily Shift';
    
    protected $guarded = ['id'];
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
}