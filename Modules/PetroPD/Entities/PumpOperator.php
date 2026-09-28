<?php

namespace Modules\PetroPD\Entities;

use App\BusinessLocation;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * PetroPD Pump Operator entity.
 *
 * Standalone PetroPD model for the existing pump_operators table.
 * This avoids runtime dependency on the retired Petro module model
 * while keeping the same shared database table used by the ERP.
 */
class PumpOperator extends Model
{
    protected $table = 'pump_operators';

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'status' => 'boolean',
        'is_default' => 'boolean',
        'can_fullscreen' => 'boolean',
        'hide_in_direct_settlement_if_pending_shifts' => 'boolean',
        'is_petro_pd_only' => 'boolean',
    ];

    /**
     * Keep the legacy active scope name available for code that calls:
     * PumpOperator::withoutGlobalScope('active')
     */
    protected static function booted()
    {
        static::addGlobalScope('active', function (Builder $builder) {
            // Do not force active filtering globally for PetroPD pages.
            // Many PD screens intentionally need both active and inactive operators.
        });
    }

    public function businessLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'pump_operator_id');
    }

    public function assignments()
    {
        return $this->hasMany(PumpOperatorAssignment::class, 'pump_operator_id');
    }

    public function settlements()
    {
        return $this->hasMany(Settlement::class, 'pump_operator_id');
    }
}
