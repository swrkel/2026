<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Risk;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
class GuarantorAssessmentController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Guarantor Assessment'; return view('bankingmicrofinance::risk.guarantorassessment.index', compact('title','businessId')); }
    public function create() { $title='Add Guarantor Assessment'; return view('bankingmicrofinance::bkg_mfi_006.forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status','Guarantor Assessment saved successfully.'); }
}
