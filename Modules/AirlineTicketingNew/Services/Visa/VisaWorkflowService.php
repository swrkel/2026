<?php
namespace Modules\AirlineTicketingNew\Services\Visa;

use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Entities\VisaApplication;
use Modules\AirlineTicketingNew\Entities\VisaStatusHistory;

class VisaWorkflowService
{
    public function changeStatus(VisaApplication $application, string $status, ?string $note = null): VisaApplication
    {
        return DB::transaction(function () use ($application, $status, $note) {
            $from = $application->status;

            $application->update(['status' => $status]);

            VisaStatusHistory::query()->create([
                'business_id' => $application->business_id,
                'business_location_id' => $application->business_location_id,
                'store_id' => $application->store_id,
                'visa_application_id' => $application->id,
                'from_status' => $from,
                'to_status' => $status,
                'note' => $note,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);

            return $application->refresh();
        });
    }
}
