<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CreditCommitteeReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Credit Committee Report'; return view('bankingmicrofinance::reports.creditcommitteereport.index', compact('title','businessId')); }
    public function create() { $title='Add Credit Committee Report'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Credit Committee Report saved successfully.'); }
}
