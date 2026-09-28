<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\LeadsNew\Models\LeadsNewLead;
use Modules\LeadsNew\Services\LeadsNewJourneyService;

class LeadsNewCustomerJourneyController extends Controller
{
    public function show($id, LeadsNewJourneyService $journeyService)
    {
        $lead = LeadsNewLead::findOrFail($id);
        return view('leadsnew::customer360.show', [
            'lead' => $lead,
            'timeline' => $journeyService->timeline((int) $id),
        ]);
    }
}
