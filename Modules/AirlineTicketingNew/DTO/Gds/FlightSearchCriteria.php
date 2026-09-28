<?php
namespace Modules\AirlineTicketingNew\DTO\Gds;

final class FlightSearchCriteria
{
    public function __construct(
        public readonly string $origin,
        public readonly string $destination,
        public readonly string $departureDate,
        public readonly ?string $returnDate,
        public readonly int $adults = 1,
        public readonly int $children = 0,
        public readonly int $infants = 0,
        public readonly ?string $cabin = null,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
