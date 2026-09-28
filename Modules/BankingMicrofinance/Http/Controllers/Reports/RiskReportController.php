<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Risk\RiskAssessmentService;
class RiskReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Risk Reports'; return view('bankingmicrofinance::reports.risk.index', compact('title','businessId')); }
    public function create() { $title='Add Risk Reports'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Risk Reports saved successfully.'); }
}
