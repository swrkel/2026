<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\PetroDirect\Entities\Concerns\RequiresReconcilerContext;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementCardPayment extends Model
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

    /**
     * Cards entered inside Petro Direct have no Pumper Dashboard source row.
     * Keep this scope reusable by Add Payment, preview, print and finalisation.
     */
    public function scopePetroDirectOwned($query)
    {
        if (SchemaCapabilityCache::hasColumn('settlement_card_payments', 'pump_payment_id')) {
            $query->whereNull('settlement_card_payments.pump_payment_id');
        }

        if (SchemaCapabilityCache::hasColumn('settlement_card_payments', 'daily_card_id')) {
            $query->whereNull('settlement_card_payments.daily_card_id');
        }

        return $query->whereNotExists(function ($linkedPayment) {
            $linkedPayment->select(DB::raw(1))
                ->from('pump_operator_payments as petro_direct_blocked_card_source')
                ->whereColumn(
                    'petro_direct_blocked_card_source.parent_id',
                    'settlement_card_payments.id'
                )
                ->where('petro_direct_blocked_card_source.payment_type', 'card');
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
}
