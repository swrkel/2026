<?php

namespace Modules\SW\Entities;

/**
 * Operators assigned to a shift.
 *
 * Deliberately NOT tied to a pump: operators are assigned to the shift, and
 * pumps are handled at settlement.
 */
class ShiftOperator extends SWModel
{
    protected $table = 'sw_shift_operators';

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'sw_shift_id');
    }
}
