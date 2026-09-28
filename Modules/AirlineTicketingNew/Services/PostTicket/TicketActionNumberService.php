<?php

namespace Modules\AirlineTicketingNew\Services\PostTicket;

use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class TicketActionNumberService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function next(int $businessId, ?int $locationId, ?int $storeId, string $type): string
    {
        $prefixes = [
            'reissue' => 'ATRIS',
            'void' => 'ATV',
            'cancellation' => 'ATC',
            'refund' => 'ATRF',
            'credit_note' => 'ATCN',
        ];

        return $this->numbers->next(
            $businessId,
            $locationId,
            $storeId,
            $type,
            $prefixes[$type] ?? 'ATX'
        );
    }
}
