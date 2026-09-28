<?php

namespace Modules\BankingTradeFinance\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTradeFinance\Services\TradeFinanceDashboardService;

class TradeFinanceController extends Controller
{
    protected TradeFinanceDashboardService $service;

    public function __construct(TradeFinanceDashboardService $service)
    {
        $this->service = $service;
    }

    public function dashboard() { return view('bankingtradefinance::dashboard.index', ['summary' => $this->service->summary()]); }
    public function lettersOfCredit() { return view('bankingtradefinance::letters_of_credit.index', ['title' => 'Letters of Credit']); }
    public function bankGuarantees() { return view('bankingtradefinance::bank_guarantees.index', ['title' => 'Bank Guarantees']); }
    public function importBills() { return view('bankingtradefinance::import_bills.index', ['title' => 'Import Bills']); }
    public function exportBills() { return view('bankingtradefinance::export_bills.index', ['title' => 'Export Bills']); }
    public function documentaryCollections() { return view('bankingtradefinance::documentary_collections.index', ['title' => 'Documentary Collections']); }
    public function shippingDocuments() { return view('bankingtradefinance::shipping_documents.index', ['title' => 'Shipping Documents']); }
    public function reports() { return view('bankingtradefinance::reports.index', ['title' => 'Trade Finance Reports']); }
    public function settings() { return view('bankingtradefinance::settings.index', ['title' => 'Trade Finance Settings']); }
}
