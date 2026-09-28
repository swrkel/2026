<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Portal;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Services\Portal\CustomerPortalService;

class CustomerPortalController extends Controller
{
    public function dashboard(CustomerPortalService $service)
    {
        $user = auth('atn_portal')->user();

        return view('airlineticketingnew::portal.customer.dashboard', [
            'metrics' => $service->dashboard((int) $user->business_id, $user),
        ]);
    }
}
