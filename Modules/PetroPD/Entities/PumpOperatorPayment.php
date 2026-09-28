<?php

namespace Modules\PetroPD\Entities;

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
            $type = strtolower(trim((string) $payment->payment_type));
            $pumperTypes = ['cash', 'card', 'cards', 'cheque', 'cheques', 'credit', 'multiple_credit', 'other', 'shortage', 'excess'];

            if (! empty($payment->pump_operator_id)
                && in_array($type, $pumperTypes, true)
                && empty($payment->shift_id)) {
                throw new \LogicException(
                    'A Pump Operator Payment cannot be created without its immutable Shift ID.'
                );
            }

            // New financial columns were introduced independently. Check each one
            // before assigning it so partially upgraded tenant schemas continue
            // to use the legacy payment_amount column without an SQL error.
            $schema = \Illuminate\Support\Facades\Schema::class;
            $legacyAmount = (float) ($payment->payment_amount ?? 0);
            $grossAmount = $legacyAmount;
            $discountAmount = 0.0;

            if ($schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                $grossAmount = (float) ($payment->gross_amount ?? $legacyAmount);
                $payment->gross_amount = $grossAmount;
            }

            if ($schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                $discountAmount = (float) ($payment->discount_amount ?? 0);
                $payment->discount_amount = $discountAmount;
            }

            if ($schema::hasColumn('pump_operator_payments', 'net_amount')) {
                $payment->net_amount = $payment->net_amount
                    ?? ($type === 'credit'
                        ? ($grossAmount - $discountAmount)
                        : $legacyAmount);
            }

            if ($schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                $payment->transaction_date = $payment->transaction_date ?? now()->toDateString();
            }

            if ($schema::hasColumn('pump_operator_payments', 'source_type')) {
                $payment->source_type = $payment->source_type ?: ('pumper_dashboard_' . $type);
            }

            static::normalizePaymentAmountColumns($payment);
            static::assertUniqueSourceIdentity($payment);
        });

        static::updating(function (self $payment) {
            $type = strtolower(trim((string) $payment->payment_type));
            if ($payment->isDirty('payment_amount')
                && $type !== 'credit'
                && $type !== 'multiple_credit') {
                $amount = (float) $payment->payment_amount;

                if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                    $payment->gross_amount = $amount;
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                    $payment->discount_amount = 0;
                }

                if (\Illuminate\Support\Facades\Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                    $payment->net_amount = $amount;
                }
            }

            static::normalizePaymentAmountColumns($payment);
            static::assertUniqueSourceIdentity($payment);
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
     */
    private static function normalizePaymentAmountColumns(self $payment): void
    {
        $precision = self::resolveCurrencyPrecision((int) $payment->business_id);

        foreach (['payment_amount', 'gross_amount', 'discount_amount', 'net_amount'] as $column) {
            $value = $payment->getAttribute($column);

            if ($value === null || $value === '') {
                continue;
            }

            if (! is_numeric($value)) {
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

    private static function assertUniqueSourceIdentity(self $payment): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn('pump_operator_payments', 'source_type')
            || ! \Illuminate\Support\Facades\Schema::hasColumn('pump_operator_payments', 'source_id')
            || empty($payment->source_type)
            || empty($payment->source_id)) {
            return;
        }

        $query = static::query()
            ->where('business_id', $payment->business_id)
            ->where('source_type', $payment->source_type)
            ->where('source_id', $payment->source_id);

        if ($payment->exists) {
            $query->where($payment->getKeyName(), '<>', $payment->getKey());
        }

        if ($query->exists()) {
            throw new \LogicException(
                'This Pumper Dashboard source transaction already has an authoritative payment record.'
            );
        }
    }

    public function pump_operator()
    {
        return $this->belongsTo('Modules\PetroPD\Entities\PumpOperator', 'pump_operators_id');
    }

    public static function getPaymentTypesArray(){
        return [
            'cash' => __('petropd::lang.cash'),
            'cheque' => __('petropd::lang.cheque'),
            'card' => __('petropd::lang.card'),
            'credit' => __('petropd::lang.credit'),
            'multiple_credit' => __('petropd::lang.multiple_credit'),
            'shortage' => __('petropd::lang.shortage'),
            'excess' => __('petropd::lang.excess'),
        ];
    }
}
