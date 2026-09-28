<?php
namespace Modules\AirlineTicketingNew\Services\Emd;

use Modules\AirlineTicketingNew\Entities\ElectronicMiscDocument;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class EmdIssueService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function issue(array $data): ElectronicMiscDocument
    {
        return ElectronicMiscDocument::query()->create(array_merge($data, [
            'emd_no' => $data['emd_no'] ?? $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'emd',
                'ATEMD'
            ),
            'status' => 'issued',
            'created_by' => auth()->id(),
        ]));
    }
}
