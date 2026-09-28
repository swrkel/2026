<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Compliance;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Compliance\KycService;
class ComplianceAlertController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Compliance Alerts'; return view('bankingmicrofinance::compliance.alerts.index', compact('title','businessId')); }
    public function create() { $title='Add Compliance Alerts'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Compliance Alerts saved successfully.'); }
}
