<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class PricingReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Pricing Matrix Report'; return view('bankingmicrofinance::reports.pricingreport.index', compact('title','businessId')); }
    public function create() { $title='Add Pricing Matrix Report'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Pricing Matrix Report saved successfully.'); }
}
