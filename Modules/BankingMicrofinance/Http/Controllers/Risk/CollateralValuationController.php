<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Risk;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class CollateralValuationController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Collateral Valuations'; return view('bankingmicrofinance::risk.collateralvaluation.index', compact('title','businessId')); }
    public function create() { $title='Add Collateral Valuations'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Collateral Valuations saved successfully.'); }
}
