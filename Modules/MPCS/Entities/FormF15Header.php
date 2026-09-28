<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\MPCS\Entities\Mpcs15FormDetails;

class FormF15Header extends Model
{
    use LogsActivity;
    
    protected $table = 'mpcs_form_f15_headers'; 
    
    protected $fillable = [
        'business_id', 
        'dated_at', 
        'created_by', 
        'created_at', 
        'updated_at'
    ];

    public function fsetting()
    {
        /*
         * IS2009: the foreign key was 'id'.
         *
         * That told Eloquent to match mpcs_form_f15_details.id against the
         * header's id, so a header with id 5 returned the DETAIL ROW whose own
         * id happened to be 5 - an unrelated record, and never the header's
         * actual settings. The correct key is f15_form_id, which is what
         * store15FormSetting() writes and what details() below already uses.
         */
        return $this->hasMany(Mpcs15FormSettings::class, 'f15_form_id');
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    public function details()
    {
        return $this->hasMany(Mpcs15FormDetails::class, 'f15_form_id'); // adjust foreign key if needed
    }

}
