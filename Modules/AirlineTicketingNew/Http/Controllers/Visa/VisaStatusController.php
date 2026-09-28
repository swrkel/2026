<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Visa;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\VisaApplication;
use Modules\AirlineTicketingNew\Services\Visa\VisaWorkflowService;

class VisaStatusController extends Controller
{
    public function update(Request $request, VisaApplication $application, VisaWorkflowService $service)
    {
        abort_unless((int) $application->business_id === (int) session('business.id'), 404);

        $data = $request->validate([
            'status' => ['required','in:draft,documents_pending,submitted,appointment_scheduled,processing,approved,rejected,completed,cancelled'],
            'note' => ['nullable','string','max:1000'],
        ]);

        $service->changeStatus($application, $data['status'], $data['note'] ?? null);

        return back()->with('status', ['success' => 1, 'msg' => 'Visa status updated successfully.']);
    }
}
