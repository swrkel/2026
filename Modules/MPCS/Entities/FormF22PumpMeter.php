<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

class FormF22PumpMeter extends Model
{
    protected $table = 'form_f22_pump_meters';

    protected $fillable = [
        'header_id',
        'pump_id',
        'pump_name',
        'product_name',
        'meter_reading'
    ];

    public function header()
    {
        return $this->belongsTo(FormF22Header::class, 'header_id');
    }
}
