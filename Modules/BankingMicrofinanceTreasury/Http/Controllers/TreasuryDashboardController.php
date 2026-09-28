<?php
namespace Modules\BankingMicrofinanceTreasury\Http\Controllers;use Illuminate\Routing\Controller;use Modules\BankingMicrofinanceTreasury\Services\TreasuryLiquidityService;
class TreasuryDashboardController extends Controller{public function index(TreasuryLiquidityService $service){$position=$service->position(auth()->user()->business_id ?? null);return view('bankingmicrofinancetreasury::dashboard.index',compact('position'));}}
