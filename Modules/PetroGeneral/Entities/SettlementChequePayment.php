<?php

namespace Modules\PetroGeneral\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\PetroGeneral\Entities\Concerns\RequiresReconcilerContext;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SettlementChequePayment extends Model
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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }
}
