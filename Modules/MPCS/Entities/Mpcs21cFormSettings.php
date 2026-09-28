<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Mpcs21cFormSettings extends Model
{
    
    protected $table = 'mpcs_21c_form_settings';
    
    protected $fillable = [
        'business_id',
        'date',
        'time',
        'starting_number',
        'ref_pre_form_number',
        'rec_sec_prev_day_amt',
        'rec_sec_opn_stock_amt',
        'issue_section_previous_day_amount',
        'manager_name',
        'categories',
        'pumps',
        'meters',
        'created_by'
    ];
}