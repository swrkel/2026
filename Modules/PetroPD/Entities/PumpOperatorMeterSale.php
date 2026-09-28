<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PumpOperatorMeterSale extends Model
{
     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * MA-002: the one definition of "a meter sale that belongs to PD
     * Settlement".
     *
     * THIS EXISTS SO THE RULE CANNOT BE FORGOTTEN AGAIN.
     *
     * Three separate queries fed the PD Settlement screen, and each had to
     * remember the same filter. One of them never did, so any sale taken
     * through the Payments page during a shift appeared in PD Settlement on
     * top of the real closing reading - and that money was charged twice,
     * once in Direct Settlement and once here.
     *
     * Anything reading meter sales for PD Settlement should now call
     *
     *     PumpOperatorMeterSale::forPdSettlement($business_id, $prefixes)
     *
     * so the rule lives in ONE place. A new query that forgets it is a
     * visible omission rather than a silent one.
     *
     * THE TWO CONDITIONS:
     *
     *   p_o_payment_id IS NULL
     *       a sale attached to a payment has been collected and settled on
     *       the Direct side, and must not be charged again here.
     *
     *   settlement_no unset, or carrying a PD prefix
     *       PDST belongs to PD Settlement, ST to Direct Settlement.
     *
     * DELIBERATELY NOT FILTERED ON source. A meter sale added by hand in
     * Manual Entry is saved with no source at all, so a source filter would
     * hide every hand-added row the moment it was saved.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $pdPrefixes  the PD settlement prefixes, e.g. ['PDST']
     */
    public function scopeForPdSettlement($query, array $pdPrefixes = ['PDST'])
    {
        return $query
            /*
             |------------------------------------------------------------------
             | Exclude a payment-linked reading only when that payment was
             | actually SETTLED - not merely because a link exists.
             |------------------------------------------------------------------
             |
             | This was a flat ->whereNull('p_o_payment_id').
             |
             | The intent behind it is right, and is documented where this scope
             | is called: a sale already collected and settled on the Direct side
             | must not be charged again here. That hazard is real and is still
             | guarded.
             |
             | But the presence of a payment link does not prove the payment was
             | settled. Shift 13 was the case that exposed it - LAD2's closing
             | reading, 951.42 litres, present and unsettled, dropped because it
             | happened to carry a payment id. The shift could not be settled at
             | all.
             |
             | The settled-ness is now TESTED:
             |
             |     is_used = 1   settled elsewhere -> still excluded
             |     is_used = 0   settled nowhere   -> loaded; counting it here is
             |                                        the first and only time
             |     no payment row                  -> loaded; nothing can have
             |                                        settled a payment that does
             |                                        not exist
             |
             | The settlement_no prefix rule below is unchanged: an ST row is
             | Direct Settlement's and is still excluded outright.
             */
            ->where(function ($unsettledOnly) {
                $unsettledOnly
                    ->whereNull('p_o_payment_id')
                    ->orWhereNotExists(function ($settled) {
                        $settled
                            ->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('pump_operator_payments')
                            ->whereColumn(
                                'pump_operator_payments.id',
                                'pump_operator_meter_sales.p_o_payment_id'
                            )
                            ->where('pump_operator_payments.is_used', 1);
                    });
            })
            ->where(function ($pdOnly) use ($pdPrefixes) {
                $pdOnly->whereNull('settlement_no')
                    ->orWhere('settlement_no', '');

                foreach ($pdPrefixes as $pdPrefix) {
                    if ($pdPrefix === null || $pdPrefix === '') {
                        continue;
                    }

                    $pdOnly->orWhere('settlement_no', 'LIKE', $pdPrefix . '%');
                }
            });
    }

    public function details()
    {
        return $this->hasMany(
            PumpOperatorMeterSaleDetail::class,
            'sale_id'
        );
    }
}
