<?php
namespace Modules\AirlineTicketingNew\Contracts\Gds;

interface GdsProviderInterface
{
    public function code(): string;
    public function authenticate(): void;
    public function searchFlights(array $criteria): array;
    public function priceItinerary(array $itinerary): array;
    public function createReservation(array $payload): array;
    public function issueTicket(array $payload): array;
    public function cancelReservation(string $providerReference): array;
}
