<?php
namespace Modules\BankingCheque\Http\Controllers;
use Illuminate\Routing\Controller;
class BankingChequeDashboardController extends Controller { public function index(){ return view('bankingcheque::dashboard.index'); } }
