<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Committee;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CreditCommitteeDecisionController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Credit Committee Decisions'; return view('bankingmicrofinance::committee.creditcommitteedecision.index', compact('title','businessId')); }
    public function create() { $title='Add Credit Committee Decisions'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Credit Committee Decisions saved successfully.'); }
}
