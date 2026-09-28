<?php

namespace Modules\AirlineTicketingNew\Entities\Relations;

use Modules\AirlineTicketingNew\Entities\PassengerDocument;
use Modules\AirlineTicketingNew\Entities\PassengerEmergencyContact;
use Modules\AirlineTicketingNew\Entities\PassengerLoyaltyAccount;
use Modules\AirlineTicketingNew\Entities\PassengerVisa;

trait PassengerRelations
{
    public function documents() { return $this->hasMany(PassengerDocument::class, 'passenger_id'); }
    public function visas() { return $this->hasMany(PassengerVisa::class, 'passenger_id'); }
    public function loyaltyAccounts() { return $this->hasMany(PassengerLoyaltyAccount::class, 'passenger_id'); }
    public function emergencyContacts() { return $this->hasMany(PassengerEmergencyContact::class, 'passenger_id'); }
}
