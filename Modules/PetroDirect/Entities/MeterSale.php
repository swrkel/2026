<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

    /**
     * Rows created by Petro Direct never carry an operational shift id.
     *
     * Pumper Dashboard/Petro PD mirror their meter entries into the legacy
     * meter_sales table with a real shift id.  Keeping this boundary on the
     * Petro Direct model prevents those shared-table rows from leaking into
     * Direct Settlement screens, totals and finalisation.
     */
    /*
     | IS2171: settlement ownership, and nothing else.
     |
     | This also tested meter_sales.shift_id for null-or-zero, and required the
     | settlement work_shift to contain "DST". Both were true only of settlements
     | created after 23 August 2026, when the shift reference changed format -
     | so 485 older settlements showed no pumps, a total missing their meter
     | sales, and an empty meter section on edit.
     |
     | The settlement_no NOT LIKE PDST% below is what actually separates this
     | module from Petro PD. The other two only excluded legitimate history.
    */
    public function scopePetroDirectOwned($query)
    {
        return $query
            ->whereExists(function ($directSettlement) {
                $directSettlement->select(DB::raw(1))
                    ->from('settlements as petro_direct_settlements')
                    ->whereColumn(
                        'petro_direct_settlements.id',
                        'meter_sales.settlement_no'
                    )
                    ->whereColumn(
                        'petro_direct_settlements.business_id',
                        'meter_sales.business_id'
                    )
                    ->where(
                        'petro_direct_settlements.settlement_no',
                        'not like',
                        'PDST%'
                    );
            });
    }

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
        return $this->belongsTo('\Modules\PetroDirect\Entities\Settlement', 'settlement_no', 'id');
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
        return $this->belongsTo('\Modules\PetroDirect\Entities\Pump', 'pump_id', 'id');
    }

}
