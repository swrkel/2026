<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Ancillary;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\AncillaryService;

class AncillaryServiceController extends Controller
{
    public function index()
    {
        $records = AncillaryService::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::ancillary.services.index', compact('records'));
    }
}
