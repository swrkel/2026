<?php

namespace Modules\PetroPD\Entities;

use Illuminate\Database\Eloquent\Model;

class PetroPdShift extends Model
{
    protected $table = 'petro_shifts';
    protected $guarded = ['id'];

    public const STATUS_OPEN = 0;
    public const STATUS_CLOSED = 1;

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeClosed($query)
    {
        return $query->whereNotNull('closed_time');
    }
}
