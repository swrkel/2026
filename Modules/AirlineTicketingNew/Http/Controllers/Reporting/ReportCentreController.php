<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Reporting;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\SavedReport;
use Modules\AirlineTicketingNew\Services\Reporting\ReportRegistry;

class ReportCentreController extends Controller
{
    public function index(ReportRegistry $registry)
    {
        return view('airlineticketingnew::reporting.centre', [
            'reports' => $registry->all(),
            'savedReports' => SavedReport::query()
                ->where('business_id', (int) session('business.id'))
                ->latest('id')
                ->get(),
        ]);
    }
}
