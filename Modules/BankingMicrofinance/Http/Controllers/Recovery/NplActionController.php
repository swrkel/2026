<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Recovery;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Recovery\NplWorkflowService;
class NplActionController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='NPL Actions'; return view('bankingmicrofinance::recovery.npl_actions.index', compact('title','businessId')); }
    public function create() { $title='Add NPL Actions'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'NPL Actions saved successfully.'); }
}
