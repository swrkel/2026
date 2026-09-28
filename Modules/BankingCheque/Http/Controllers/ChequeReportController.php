<?php
namespace Modules\BankingCheque\Http\Controllers;
use Illuminate\Routing\Controller;
class ChequeReportController extends Controller { public function show($report){ return view('bankingcheque::reports.show', compact('report')); } }
