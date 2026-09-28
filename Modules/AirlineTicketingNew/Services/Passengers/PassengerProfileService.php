<?php

namespace Modules\AirlineTicketingNew\Services\Passengers;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\Passenger;

class PassengerProfileService
{
    public function create(array $data): Passenger
    {
        return DB::transaction(fn () => Passenger::query()->create($data));
    }

    public function update(Passenger $passenger, array $data): Passenger
    {
        return DB::transaction(function () use ($passenger, $data): Passenger {
            $passenger->fill($data)->save();
            return $passenger->refresh();
        });
    }
}
