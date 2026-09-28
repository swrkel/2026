<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Bundles;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\ServiceBundle;

class ServiceBundleController extends Controller
{
    public function index()
    {
        $records = ServiceBundle::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::bundles.index', compact('records'));
    }
}
