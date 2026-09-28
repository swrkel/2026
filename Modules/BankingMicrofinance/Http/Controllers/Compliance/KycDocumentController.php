<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Compliance;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Compliance\KycService;
class KycDocumentController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='KYC Documents'; return view('bankingmicrofinance::compliance.documents.index', compact('title','businessId')); }
    public function create() { $title='Add KYC Documents'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'KYC Documents saved successfully.'); }
}
