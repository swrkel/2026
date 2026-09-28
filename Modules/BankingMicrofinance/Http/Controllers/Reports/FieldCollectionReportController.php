<?php

namespace Modules\BankingMicrofinance\Http\Controllers\Reports;

use Illuminate\Routing\Controller;

class FieldCollectionReportController extends Controller
{
    public function daily() { return view('bankingmicrofinance::reports.field.daily_collection'); }
    public function productivity() { return view('bankingmicrofinance::reports.field.officer_productivity'); }
    public function receiptExceptions() { return view('bankingmicrofinance::reports.field.receipt_exceptions'); }
    public function cashHandoverVariance() { return view('bankingmicrofinance::reports.field.cash_handover_variance'); }
}
