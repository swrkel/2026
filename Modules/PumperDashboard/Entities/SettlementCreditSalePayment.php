<?php

namespace Modules\PumperDashboard\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementCreditSalePayment extends Model
{
    protected $fillable = [];

    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'Settlement Payments';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (self $detail) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')
                || empty($detail->pump_payment_id)) {
                return;
            }

            $master = \Illuminate\Support\Facades\DB::table('pump_operator_payments')
                ->where('id', $detail->pump_payment_id)
                ->first(['id', 'business_id', 'pump_operator_id', 'shift_id']);

            if (! $master) {
                throw new \LogicException('Credit-sale detail has no valid master Pump Operator Payment.');
            }

            // One financial master payment must have exactly one credit-sale
            // header. Product rows belong in voucher/item detail tables.
            $duplicateMasterLink = static::where('pump_payment_id', $detail->pump_payment_id)
                ->when($detail->exists, function ($query) use ($detail) {
                    $query->where($detail->getKeyName(), '<>', $detail->getKey());
                })
                ->exists();

            if ($duplicateMasterLink) {
                throw new \LogicException(
                    'A Pump Operator Payment cannot be linked to more than one Credit Sale header.'
                );
            }

            $bindings = [
                'business_id' => (int) $master->business_id,
                'pump_operator_id' => (int) $master->pump_operator_id,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                $bindings['shift_id'] = (int) $master->shift_id;
            }

            foreach ($bindings as $column => $masterValue) {
                $detailValue = $detail->{$column};
                if ($detailValue === null || $detailValue === '' || (int) $detailValue === 0) {
                    $detail->{$column} = $masterValue;
                    continue;
                }

                if ((int) $detailValue !== $masterValue) {
                    throw new \LogicException(
                        sprintf('Credit-sale detail %s must match its master Pump Operator Payment.', $column)
                    );
                }
            }

            if (\Illuminate\Support\Facades\Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                && $detail->exists
                && $detail->isDirty('shift_id')) {
                $originalShiftId = $detail->getOriginal('shift_id');
                if ($originalShiftId !== null
                    && (int) $originalShiftId > 0
                    && (int) $detail->shift_id !== (int) $originalShiftId) {
                    throw new \LogicException('The Credit Sale Shift ID is immutable and cannot be changed.');
                }
            }
        });
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




