<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Risk;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Risk\RiskAssessmentService;
class CollateralController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Collateral Register'; return view('bankingmicrofinance::risk.collaterals.index', compact('title','businessId')); }
    public function create() { $title='Add Collateral Register'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Collateral Register saved successfully.'); }
}
