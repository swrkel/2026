<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CreditPipelineReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Credit Pipeline Report'; return view('bankingmicrofinance::reports.creditpipelinereport.index', compact('title','businessId')); }
    public function create() { $title='Add Credit Pipeline Report'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Credit Pipeline Report saved successfully.'); }
}
