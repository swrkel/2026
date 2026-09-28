<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Settings;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Approval\ApprovalMatrixService;
class ApprovalMatrixController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='Approval Matrix'; return view('bankingmicrofinance::settings.approval_matrix.index', compact('title','businessId')); }
    public function create() { $title='Add Approval Matrix'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'Approval Matrix saved successfully.'); }
}
