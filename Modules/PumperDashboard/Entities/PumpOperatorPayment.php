<?php

namespace Modules\PumperDashboard\Entities;

use Illuminate\Database\Eloquent\Model;

class PumpOperatorPayment extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'pump_operator_payments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::creating(function (self $payment) {
            static::normalizePaymentAmountColumns($payment);
        });

        static::updating(function (self $payment) {
            static::normalizePaymentAmountColumns($payment);

            if (! $payment->isDirty('shift_id')) {
                return;
            }

            $originalShiftId = $payment->getOriginal('shift_id');
            $newShiftId = $payment->shift_id;

            // A missing legacy Shift ID may be backfilled once. After that the
            // source Shift ID is immutable for the lifetime of the payment.
            if ($originalShiftId !== null
                && (int) $originalShiftId > 0
                && (int) $newShiftId !== (int) $originalShiftId) {
                throw new \LogicException(
                    'The Pump Operator Payment Shift ID is immutable and cannot be changed.'
                );
            }
        });
    }

    /**
     * S 639: nothing with a floating-point tail reaches the amount column.
     *
     * payment_amount is declared as string($table->string('payment_amount') in
     * 2025_05_31_213714_create_pump_operator_payments_table), so the column
     * stores exactly what PHP hands it. A shortage computed as
     * 442.774999999994... was stored verbatim and then rounded differently by
     * different screens.
     *
     * This normalises on every save, whatever the caller, so the guarantee does
     * not depend on each write path remembering to round. It is the last line of
     * defence while the column remains a string; converting it to decimal(20,2)
     * is a separate change that has to be rehearsed on a clone first.
     *
     * The same guard exists on Modules\\PetroPD\\Entities\\PumpOperatorPayment,
     * because the two modules map their own model onto this one table.
     */
    private static function normalizePaymentAmountColumns(self $payment): void
    {
        $precision = self::resolveCurrencyPrecision((int) $payment->business_id);

        foreach (['payment_amount', 'gross_amount', 'discount_amount', 'net_amount'] as $column) {
            $value = $payment->getAttribute($column);

            if ($value === null || $value === '' || ! is_numeric($value)) {
                continue;
            }

            $payment->setAttribute(
                $column,
                number_format(round((float) $value, $precision), $precision, '.', '')
            );
        }
    }

    private static function resolveCurrencyPrecision(int $business_id): int
    {
        static $cache = [];

        if (array_key_exists($business_id, $cache)) {
            return $cache[$business_id];
        }

        try {
            $precision = \Illuminate\Support\Facades\DB::table('business')
                ->where('id', $business_id)
                ->value('currency_precision');
        } catch (\Throwable $e) {
            $precision = null;
        }

        return $cache[$business_id] = ($precision === null || $precision === '') ? 2 : (int) $precision;
    }

    public function pump_operator()
    {
        return $this->belongsTo('Modules\PumperDashboard\Entities\PumpOperator', 'pump_operators_id');
    }

    public static function getPaymentTypesArray(){
        return [
            'cash' => __('pumperdashboard::lang.cash'),
            'cheque' => __('pumperdashboard::lang.cheque'),
            'card' => __('pumperdashboard::lang.card'),
            'credit' => __('pumperdashboard::lang.credit'),
            'multiple_credit' => __('pumperdashboard::lang.multiple_credit'),
            'shortage' => __('pumperdashboard::lang.shortage'),
            'excess' => __('pumperdashboard::lang.excess'),
        ];
    }
}




