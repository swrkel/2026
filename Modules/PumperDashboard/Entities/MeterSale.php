<?php

namespace Modules\PumperDashboard\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MeterSale extends Model
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
    * Get the settlement that belongs to the subscription.
    */
    public function settlements()
    {
        return $this->belongsTo('\Modules\PumperDashboard\Entities\Settlement', 'settlement_no', 'id');
    }

    /**
    * Get the products that belongs to the subscription.
    */
    public function products()
    {
        return $this->belongsTo('App\Product');
    }

    /**
    * Get the product that belongs to the meter sale.
    */
    public function product()
    {
        return $this->belongsTo('App\Product', 'product_id', 'id');
    }

    /**
    * Get the pump that belongs to the meter sale.
    */
    public function pump()
    {
        return $this->belongsTo('\Modules\PumperDashboard\Entities\Pump', 'pump_id', 'id');
    }

}




