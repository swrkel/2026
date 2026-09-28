<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class GuarantorReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Guarantor Exposure Report'; return view('bankingmicrofinance::reports.guarantorreport.index', compact('title','businessId')); }
    public function create() { $title='Add Guarantor Exposure Report'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Guarantor Exposure Report saved successfully.'); }
}
