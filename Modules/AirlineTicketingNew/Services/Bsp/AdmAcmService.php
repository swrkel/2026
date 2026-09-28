<?php
namespace Modules\AirlineTicketingNew\Services\Bsp;

use Modules\AirlineTicketingNew\Entities\AgencyCreditMemo;
use Modules\AirlineTicketingNew\Entities\AgencyDebitMemo;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class AdmAcmService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function createAdm(array $data): AgencyDebitMemo
    {
        return AgencyDebitMemo::query()->create(array_merge($data, [
            'adm_no' => $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'adm',
                'ATADM'
            ),
            'status' => 'open',
        ]));
    }

    public function createAcm(array $data): AgencyCreditMemo
    {
        return AgencyCreditMemo::query()->create(array_merge($data, [
            'acm_no' => $this->numbers->next(
                $data['business_id'],
                $data['business_location_id'] ?? null,
                $data['store_id'] ?? null,
                'acm',
                'ATACM'
            ),
            'status' => 'open',
        ]));
    }
}
