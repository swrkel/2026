<?php

namespace Modules\AirlineTicketingNew\Services\Visa;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\VisaApplication;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;

class VisaApplicationService
{
    public function __construct(private readonly DocumentNumberService $numbers)
    {
    }

    public function create(array $data, array $checklist): VisaApplication
    {
        return DB::transaction(function () use ($data, $checklist): VisaApplication {
            $application = VisaApplication::query()->create(array_merge($data, [
                'application_no' => $this->numbers->next(
                    $data['business_id'],
                    $data['business_location_id'] ?? null,
                    $data['store_id'] ?? null,
                    'visa_application',
                    'ATVA'
                ),
                'status' => $data['status'] ?? 'draft',
            ]));

            foreach ($checklist as $item) {
                $application->checklistItems()->create(array_merge($item, [
                    'business_id' => $application->business_id,
                    'business_location_id' => $application->business_location_id,
                    'store_id' => $application->store_id,
                ]));
            }

            return $application->fresh('checklistItems');
        });
    }
}
