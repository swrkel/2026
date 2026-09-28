<?php

namespace Modules\SettlementSW\Entities;

use Modules\SettlementSW\Services\SettlementSwSchema;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Pump extends Model
{
    protected $fillable = [];

    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    
    protected static $logName = 'Pumps'; 

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

    /**
     * Scope a query to only include other sales pumps.
     */
    public function scopeOtherSales($query)
    {
        if (!SettlementSwSchema::hasColumn('pumps', 'is_other_sales_pump')) {
            return $query;
        }

        return $query->where('is_other_sales_pump', 1);
    }

    /**
     * Scope a query to exclude other sales pumps.
     */
    public function scopeNotOtherSales($query)
    {
        if (!SettlementSwSchema::hasColumn('pumps', 'is_other_sales_pump')) {
            return $query;
        }

        return $query->where('is_other_sales_pump', 0);
    }
}
