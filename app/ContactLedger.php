<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactLedger extends Model
{
    use LogsActivity;
    use SoftDeletes;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;


    protected static $logName = 'Contact Ledger';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Boot method to clear outstanding cache when ledger changes
     */
    protected static function boot()
    {
        parent::boot();

        // Clear cache on create, update, delete
        static::created(function ($ledger) {
            if (!empty($ledger->business_id)) {
                \App\Http\Controllers\ContactController::clearOutstandingCache($ledger->business_id);
            }
        });

        static::updated(function ($ledger) {
            if (!empty($ledger->business_id)) {
                \App\Http\Controllers\ContactController::clearOutstandingCache($ledger->business_id);
            }
        });

        static::deleted(function ($ledger) {
            if (!empty($ledger->business_id)) {
                \App\Http\Controllers\ContactController::clearOutstandingCache($ledger->business_id);
            }
        });
    }

    /**
     * Creates new contact ledger transaction
     * @return obj
     */
     public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
    public static function createContactLedger($data, $page = null)
    {
        // dd($data);
        $ledger_data = [
            'business_id' => !empty($data['business_id']) ? $data['business_id'] : request()->session()->get('user.business_id'),
            'contact_id' => $data['contact_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'page' => $page,
            'sub_type' => !empty($data['sub_type']) ? $data['sub_type'] : null,
            'operation_date' => !empty($data['operation_date']) ? $data['operation_date'] : \Carbon::now(),
            'created_by' => $data['created_by'],
            'transaction_id' => !empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'transaction_payment_id' => !empty($data['transaction_payment_id']) ? $data['transaction_payment_id'] : null,
            'note' => !empty($data['note']) ? $data['note'] : null,
            'transaction_sell_line_id' => !empty($data['transaction_sell_line_id']) ? $data['transaction_sell_line_id'] : null,
            'income_type' => !empty($data['income_type']) ? $data['income_type'] : null,
            'installment_id' => !empty($data['installment_id']) ? $data['installment_id'] : null,
        ];
        $contact_ledger = ContactLedger::create($ledger_data);

        return $contact_ledger;
    }
}
