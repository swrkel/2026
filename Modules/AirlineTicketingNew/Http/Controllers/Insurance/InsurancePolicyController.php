<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Insurance;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\TravelInsurancePolicy;

class InsurancePolicyController extends Controller
{
    public function index()
    {
        $records = TravelInsurancePolicy::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(50);

        return view('airlineticketingnew::insurance.policies.index', compact('records'));
    }
}
