<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Financial;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CashflowAnalysisController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Cashflow Analysis'; return view('bankingmicrofinance::financial.cashflowanalysis.index', compact('title','businessId')); }
    public function create() { $title='Add Cashflow Analysis'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Cashflow Analysis saved successfully.'); }
}
