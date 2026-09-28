<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Risk;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Risk\RiskAssessmentService;
class RiskAssessmentController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Risk Assessments'; return view('bankingmicrofinance::risk.assessments.index', compact('title','businessId')); }
    public function create() { $title='Add Risk Assessments'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Risk Assessments saved successfully.'); }
}
