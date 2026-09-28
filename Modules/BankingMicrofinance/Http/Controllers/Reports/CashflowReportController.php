<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CashflowReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Cashflow Affordability Report'; return view('bankingmicrofinance::reports.cashflowreport.index', compact('title','businessId')); }
    public function create() { $title='Add Cashflow Affordability Report'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Cashflow Affordability Report saved successfully.'); }
}
