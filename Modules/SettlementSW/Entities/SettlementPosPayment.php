<?php

namespace Modules\SettlementSW\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementPosPayment extends Model
{
    protected $fillable = [];

    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Settlement POS Payments';

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
     * Customer (contact) linked to this POS payment.
     */
    public function customer()
    {
        return $this->belongsTo(\App\Contact::class, 'customer_id');
    }
}