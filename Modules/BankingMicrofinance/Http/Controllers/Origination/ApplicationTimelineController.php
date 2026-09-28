<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Origination;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class ApplicationTimelineController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Application Timeline'; return view('bankingmicrofinance::origination.applicationtimeline.index', compact('title','businessId')); }
    public function create() { $title='Add Application Timeline'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Application Timeline saved successfully.'); }
}
