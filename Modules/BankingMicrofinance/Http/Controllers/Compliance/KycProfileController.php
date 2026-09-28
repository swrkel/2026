<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Compliance;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingMicrofinance\Services\Compliance\KycService;
class KycProfileController extends Controller {
    public function index(Request $request) { $businessId=(int)session('business.id'); $title='KYC Profiles'; return view('bankingmicrofinance::compliance.kyc.index', compact('title','businessId')); }
    public function create() { $title='Add KYC Profiles'; return view('bankingmicrofinance::forms.generic', compact('title')); }
    public function store(Request $request) { return back()->with('status', 'KYC Profiles saved successfully.'); }
}
