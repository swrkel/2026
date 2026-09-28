<?php

namespace Modules\LeadsNew\Http\Controllers\Opportunity;

use Illuminate\Routing\Controller;
use Modules\LeadsNew\Models\LeadsNewOpportunity;

class LeadsNewOpportunityCentreController extends Controller
{
    public function index()
    {
        $opportunities = LeadsNewOpportunity::query()->orderByDesc('id')->paginate(25);
        return view('leadsnew::opportunities.index', compact('opportunities'));
    }
}
