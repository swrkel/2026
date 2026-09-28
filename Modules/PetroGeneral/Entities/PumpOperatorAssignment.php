<?php

namespace Modules\PetroGeneral\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PumpOperatorAssignment extends Model
{
     /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
    
    public function Shift(){
        return $this->belongsTo(PetroShift::class,'shift_id');
    }

    public function pumpOperator()
    {
        return $this->belongsTo(PumpOperator::class, 'pump_operator_id');
    }
    
    public function pump()
    {
        return $this->belongsTo(Pump::class, 'pump_id');
    }

    /**
     * Boot method to clear dashboard cache when assignments change
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($assignment) {
            if (!empty($assignment->pump_operator_id)) {
                Cache::forget("dashboard_unconfirmed_meters_{$assignment->pump_operator_id}");
            }
        });

        static::deleted(function ($assignment) {
            if (!empty($assignment->pump_operator_id)) {
                Cache::forget("dashboard_unconfirmed_meters_{$assignment->pump_operator_id}");
            }
        });
    }
}
