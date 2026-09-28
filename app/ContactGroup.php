<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ContactGroup extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*']; 

    protected static $logName = 'Customer Group'; 

    protected $fillable = [
        'name',
        'business_id',
        'type',
        'amount',
        'maximum_discount',
        'last_maximum_discount',
        'account_type_id',
        'interest_account_id',
        'supplier_group_id',
        'created_by',
    ];

    protected $dates = ['deleted_at'];

    protected $guarded = ['id'];

    public static function forDropdown($business_id, $prepend_none = true, $prepend_all = false, $type = null)
    {
        if(empty($type)){
            $type = 'customer';
        }
        $all_cg = ContactGroup::where('business_id', $business_id)->where('type', $type);
        $all_cg = $all_cg->pluck('name', 'id');

        // Prepend none
        if ($prepend_none) {
            $all_cg = $all_cg->prepend(__("lang_v1.none"), '');
        }

        // Prepend all
        if ($prepend_all) {
            $all_cg = $all_cg->prepend(__("report.all"), '');
        }
        
        return $all_cg;
    }

    // Set up activity log options
    public function getActivitylogOptions(): LogOptions
    {
        logger("Activitylog options called with: " . json_encode($this->getAttributes()));


        return LogOptions::defaults()
        ->logOnly(['*']);
    }
}
