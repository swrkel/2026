<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Origination;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class LoanOriginationController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Loan Origination Pipeline'; return view('bankingmicrofinance::origination.loanorigination.index', compact('title','businessId')); }
    public function create() { $title='Add Loan Origination Pipeline'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Loan Origination Pipeline saved successfully.'); }
}
