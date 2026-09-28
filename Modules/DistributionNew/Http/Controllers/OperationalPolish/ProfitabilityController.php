<?php

namespace Modules\DistributionNew\Http\Controllers\OperationalPolish;

use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\OperationalPolish\ProfitabilityService;

class ProfitabilityController extends Controller
{
    public function index()
    {
        return view('distributionnew::operational_polish.profitability.index');
    }
}
