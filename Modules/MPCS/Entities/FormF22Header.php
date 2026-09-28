<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FormF22Header extends Model
{
    use LogsActivity;

    /**
     * Explicit table name for the F22 stock taking header records.
     */
    protected $table = 'form_f22_headers';

    /**
     * Explicitly allow the fields used by F22FormController::saveF22Form().
     * This prevents header creation from silently failing or being blocked by
     * mass-assignment differences between Laravel/module versions.
     */
    protected $fillable = [
        'form_no',
        'business_id',
        'location_id',
        'manager_name',
        'approved_by',
        'is_approved',
        'form_date',
        'purchase_price1',
        'purchase_price2',
        'purchase_price3',
        'sales_price1',
        'sales_price2',
        'sales_price3',
        'status',
        'created_by',
        'pre_field',
    ];

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected static $logName = 'Form F22 Header';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
