<?php

namespace Modules\AirlineTicketingNew\Entities\Relations;

use Modules\AirlineTicketingNew\Entities\ReservationPassenger;
use Modules\AirlineTicketingNew\Entities\ReservationSegment;
use Modules\AirlineTicketingNew\Entities\ReservationStatusHistory;

trait ReservationRelations
{
    public function segments()
    {
        return $this->hasMany(ReservationSegment::class, 'reservation_id')->orderBy('segment_no');
    }

    public function passengers()
    {
        return $this->hasMany(ReservationPassenger::class, 'reservation_id');
    }

    public function statusHistory()
    {
        return $this->hasMany(ReservationStatusHistory::class, 'reservation_id')->orderByDesc('changed_at');
    }
}
