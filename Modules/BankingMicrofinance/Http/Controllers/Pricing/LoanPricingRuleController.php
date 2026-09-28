<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Pricing;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class LoanPricingRuleController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Loan Pricing Rules'; return view('bankingmicrofinance::pricing.loanpricingrule.index', compact('title','businessId')); }
    public function create() { $title='Add Loan Pricing Rules'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Loan Pricing Rules saved successfully.'); }
}
