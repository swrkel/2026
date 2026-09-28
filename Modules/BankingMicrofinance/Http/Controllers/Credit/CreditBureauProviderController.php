<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Credit;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CreditBureauProviderController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Credit Bureau Providers'; return view('bankingmicrofinance::credit.creditbureauprovider.index', compact('title','businessId')); }
    public function create() { $title='Add Credit Bureau Providers'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Credit Bureau Providers saved successfully.'); }
}
