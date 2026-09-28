<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\PetroDirect\Entities\Concerns\RequiresReconcilerContext;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementCreditSalePayment extends Model
{
    protected $fillable = [];

    use LogsActivity;
    use RequiresReconcilerContext;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'Settlement Payments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /** Credit sales entered by Petro Direct, excluding Pumper Dashboard/Petro PD. */
    public function scopePetroDirectOwned($query)
    {
        if (SchemaCapabilityCache::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
            $query->whereNull('settlement_credit_sale_payments.pump_payment_id');
        }

        if (SchemaCapabilityCache::hasColumn('settlement_credit_sale_payments', 'is_from_pumper')) {
            $query->where(function ($owned) {
                $owned->whereNull('settlement_credit_sale_payments.is_from_pumper')
                    ->orWhere('settlement_credit_sale_payments.is_from_pumper', 0);
            });
        }

        return $query;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }
}
