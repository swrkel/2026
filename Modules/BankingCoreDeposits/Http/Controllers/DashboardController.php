<?php
namespace Modules\BankingCoreDeposits\Http\Controllers;
use Illuminate\Routing\Controller; use Modules\BankingCoreDeposits\Entities\DepositAccount; use Modules\BankingCoreDeposits\Entities\DepositTransaction; use Modules\BankingCoreDeposits\Entities\FixedDeposit;
class DashboardController extends Controller { public function index() { $summary = ['active_accounts'=>DepositAccount::where('status','active')->count(), 'dormant_accounts'=>DepositAccount::where('status','dormant')->count(), 'total_deposits'=>DepositTransaction::where('type','deposit')->sum('amount'), 'maturing_fds'=>FixedDeposit::where('status','active')->whereDate('maturity_date','<=',now()->addDays(30))->count()]; return view('bankingcoredeposits::dashboard.index', compact('summary')); } }
