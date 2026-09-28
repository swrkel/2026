<?php
namespace Modules\BankingCoreDeposits\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\BankingCoreDeposits\Services\InterestEngineService;
class InterestController extends Controller { public function accrualPreview(){ return view('bankingcoredeposits::interest.preview'); } public function post(Request $request, InterestEngineService $engine){ $count=$engine->accrueForDate($request->date ?? now()->toDateString()); return back()->with('status',"Interest accrual generated for {$count} accounts"); } }
