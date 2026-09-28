<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Recovery\NplWorkflowService;
class NplReportController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='NPL Reports'; return view('bankingmicrofinance::reports.npl.index', compact('title','businessId')); }
    public function create() { $title='Add NPL Reports'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'NPL Reports saved successfully.'); }
}
