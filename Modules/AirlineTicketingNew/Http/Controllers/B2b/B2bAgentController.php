<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\B2b;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\B2bAgent;

class B2bAgentController extends Controller
{
    public function index()
    {
        $records = B2bAgent::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::b2b.agents.index', compact('records'));
    }
}
