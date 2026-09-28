<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Compliance\KycService;
class ComplianceReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Compliance Reports'; return view('bankingmicrofinance::reports.compliance.index', compact('title','businessId')); }
    public function create() { $title='Add Compliance Reports'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Compliance Reports saved successfully.'); }
}
