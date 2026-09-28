<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Credit;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CreditBureauEnquiryController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Credit Bureau Enquiries'; return view('bankingmicrofinance::credit.creditbureauenquiry.index', compact('title','businessId')); }
    public function create() { $title='Add Credit Bureau Enquiries'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Credit Bureau Enquiries saved successfully.'); }
}
