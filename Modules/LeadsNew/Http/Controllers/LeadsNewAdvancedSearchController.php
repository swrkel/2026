<?php

namespace Modules\LeadsNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Services\LeadsNewAdvancedSearchService;

class LeadsNewAdvancedSearchController extends Controller
{
    public function index()
    {
        return view('leadsnew::search.index');
    }

    public function results(Request $request, LeadsNewAdvancedSearchService $service)
    {
        $businessId = $request->session()->get('user.business_id');
        $leads = $service->query($businessId, $request->all())->paginate(50);
        return view('leadsnew::search.results', compact('leads'));
    }
}
