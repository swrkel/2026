<?php

namespace Modules\FinanceReports\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\View;
use Modules\FinanceReports\Services\FinanceReportsDataService;
use Modules\FinanceReports\Services\Engine\FinanceReportEngine;
use Modules\FinanceReports\Services\Audit\FinanceReportsStandaloneAuditService;
use Modules\FinanceReports\Services\Export\FinanceReportExportService;
use Modules\FinanceReports\Services\Print\FinanceReportPrintService;
use Modules\FinanceReports\Services\DrillDown\FinanceReportDrillDownService;
use Modules\FinanceReports\Services\Finalization\FinanceReportSchedulerService;
use Modules\FinanceReports\Services\Finalization\FinancialPackService;
use Modules\FinanceReports\Services\Finalization\ExecutiveKpiService;
use Modules\FinanceReports\Services\Finalization\ProductionReadinessService;

class FinanceReportsController extends Controller
{
    protected FinanceReportsDataService $service;
    protected FinanceReportEngine $engine;

    public function __construct(FinanceReportsDataService $service, FinanceReportEngine $engine)
    {
        $this->registerFinanceReportsViewNamespace();

        $this->service = $service;
        $this->engine = $engine;
    }

    /**
     * Safety registration for FinanceReports views.
     *
     * Some installations load FinanceReports routes manually from tenant.php while the
     * nwidart module provider is still disabled/not booted. In that case Laravel does
     * not know the `financereports::` view namespace and throws:
     * "No hint path defined for [financereports]".
     *
     * Registering the namespace here is harmless when the service provider is already
     * loaded, and it protects all Finance Reports pages from the same error.
     */
    private function registerFinanceReportsViewNamespace(): void
    {
        $viewsPath = base_path('Modules/FinanceReports/Resources/views');

        if (is_dir($viewsPath)) {
            View::addNamespace('financereports', $viewsPath);
        }
    }

    public function dashboard(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);

        $pl = $this->service->incomeStatement($business_id, $start, $end, $location_id);
        $bs = $this->service->balanceSheet($business_id, $end, $location_id);
        $tb = $this->service->trialBalance($business_id, $start, $end, $location_id);

        return view('financereports::reports.dashboard', compact('locations', 'start', 'end', 'location_id', 'pl', 'bs', 'tb'));
    }

    public function trialBalance(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        // Trial Balance is a closing-balance statement at a point in time.
        // Accept legacy end_date links too, but present one unambiguous As At date.
        $as_at = $request->input('as_at') ?: $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->trialBalance($business_id, null, $as_at, $location_id);

        $active_report_tab = 'trial-balance';

        return view('financereports::reports.trial_balance', compact('locations', 'as_at', 'location_id', 'report', 'active_report_tab'));
    }

    public function balanceSheet(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->balanceSheet($business_id, $as_at, $location_id);

        $active_report_tab = 'balance-sheet';

        return view('financereports::reports.balance_sheet', compact('locations', 'as_at', 'location_id', 'report', 'active_report_tab'));
    }

    public function profitLoss(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->incomeStatement($business_id, $start, $end, $location_id);
        $active_report_tab = 'profit-loss';

        /*
         * IS2059: the nine profit breakdowns.
         *
         * The requested tab arrives as ?profit_tab=..., defaulting to products.
         * Only that one is computed - see ProfitBreakdownService - so the page
         * runs a single grouped query rather than nine.
         *
         * The tab is validated against the known list, so a hand-edited query
         * string cannot reach the grouping switch with an unexpected value.
         */
        $breakdown = app(\Modules\FinanceReports\Services\ProfitBreakdownService::class);

        $profit_tabs = $breakdown->tabs();
        $profit_tab = $request->input('profit_tab', 'products');

        if (! $breakdown->isValidTab($profit_tab)) {
            $profit_tab = 'products';
        }

        /*
         * IS2059: optional "split by location" toggle.
         *
         * Passed through as ?split_location=1. The service ignores it on the
         * locations tab and when a single location is already filtered, so the
         * view can offer it without having to know those rules.
         */
        $split_location = (bool) $request->input('split_location');

        $profit_rows = $breakdown->breakdown(
            $business_id,
            $profit_tab,
            $start,
            $end,
            $location_id,
            $split_location
        );

        // Whether the column is actually shown - the service may have declined
        // the split, and the view must not print an empty column in that case.
        $show_location_column = $split_location
            && $profit_tab !== 'locations'
            && empty($location_id);

        $profit_totals = $breakdown->totals($profit_rows);

        return view('financereports::reports.profit_loss', compact(
            'locations',
            'start',
            'end',
            'location_id',
            'report',
            'active_report_tab',
            'profit_tabs',
            'profit_tab',
            'profit_rows',
            'profit_totals',
            'split_location',
            'show_location_column'
        ));
    }

    public function incomeStatement(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->incomeStatement($business_id, $start, $end, $location_id);
        $active_report_tab = 'income-statement';

        return view('financereports::reports.income_statement', compact(
            'locations',
            'start',
            'end',
            'location_id',
            'report',
            'active_report_tab'
        ));
    }

    public function accountLedger(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $accounts = $this->service->accountsForDropdown($business_id);
        $account_id = (int) $request->input('account_id');
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $account_id > 0 ? $this->service->accountLedger($business_id, $account_id, $start, $end, $location_id) : null;

        return view('financereports::reports.account_ledger', compact('locations', 'accounts', 'account_id', 'start', 'end', 'location_id', 'report'));
    }


    public function generalLedger(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $accounts = $this->service->accountsForDropdown($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $account_id = (int) $request->input('account_id');
        $report = $this->service->generalLedger($business_id, $start, $end, $location_id, $account_id ?: null);

        return view('financereports::reports.general_ledger', compact('locations', 'accounts', 'start', 'end', 'location_id', 'account_id', 'report'));
    }

    public function cashBook(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->bookByKeyword($business_id, $start, $end, $location_id, ['cash']);

        return view('financereports::reports.book', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Cash Book - New']);
    }

    public function bankBook(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->bookByKeyword($business_id, $start, $end, $location_id, ['bank']);

        return view('financereports::reports.book', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Bank Book - New']);
    }

    public function journalRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->journalRegister($business_id, $start, $end, $location_id);

        return view('financereports::reports.journal_register', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function dayBook(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $date = $request->input('date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->dayBook($business_id, $date, $location_id);

        return view('financereports::reports.day_book', compact('locations', 'date', 'location_id', 'report'));
    }


    public function financialDashboard(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->managementDashboard($business_id, $start, $end, $location_id);

        return view('financereports::reports.financial_dashboard', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function budgetVsActual(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->budgetVsActual($business_id, $start, $end, $location_id);

        return view('financereports::reports.budget_vs_actual', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function revenueAnalysis(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->analysisBySection($business_id, $start, $end, $location_id, 'Revenue');

        return view('financereports::reports.analysis', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Revenue Analysis - New']);
    }

    public function expenseAnalysis(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->analysisBySection($business_id, $start, $end, $location_id, 'Expenses');

        return view('financereports::reports.analysis', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Expense Analysis - New']);
    }

    public function branchPerformance(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->branchPerformance($business_id, $start, $end, $location_id);

        return view('financereports::reports.branch_performance', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function financialRatios(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->financialRatios($business_id, $start, $end, $location_id);

        return view('financereports::reports.financial_ratios', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function comparativeReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->comparativeReport($business_id, $start, $end, $location_id);

        return view('financereports::reports.comparative_report', compact('locations', 'start', 'end', 'location_id', 'report'));
    }



    public function customerOutstanding(Request $request)
    {
        return $this->receivablePayableReport($request, 'customer', 'Customer Outstanding - New', 'customer_outstanding');
    }

    public function supplierOutstanding(Request $request)
    {
        return $this->receivablePayableReport($request, 'supplier', 'Supplier Outstanding - New', 'supplier_outstanding');
    }

    public function customerAging(Request $request)
    {
        return $this->agingReport($request, 'customer', 'Customer Aging - New', 'customer_aging');
    }

    public function supplierAging(Request $request)
    {
        return $this->agingReport($request, 'supplier', 'Supplier Aging - New', 'supplier_aging');
    }

    public function collectionAnalysis(Request $request)
    {
        return $this->paymentMovementReport($request, 'customer', 'Collection Analysis - New', 'collection_analysis');
    }

    public function paymentAnalysis(Request $request)
    {
        return $this->paymentMovementReport($request, 'supplier', 'Payment Analysis - New', 'payment_analysis');
    }

    public function receivableSummary(Request $request)
    {
        return $this->summaryReport($request, 'customer', 'Receivable Summary - New', 'receivable_summary');
    }

    public function payableSummary(Request $request)
    {
        return $this->summaryReport($request, 'supplier', 'Payable Summary - New', 'payable_summary');
    }

    public function customerStatement(Request $request)
    {
        return $this->statementReport($request, 'customer', 'Customer Statement - New', 'customer_statement');
    }

    public function supplierStatement(Request $request)
    {
        return $this->statementReport($request, 'supplier', 'Supplier Statement - New', 'supplier_statement');
    }

    private function receivablePayableReport(Request $request, string $contactType, string $title, string $view)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->contactOutstanding($business_id, $contactType, $as_at, $location_id);

        return view('financereports::reports.receivable_payable', compact('locations', 'as_at', 'location_id', 'report', 'title'));
    }

    private function agingReport(Request $request, string $contactType, string $title, string $view)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->contactAging($business_id, $contactType, $as_at, $location_id);

        return view('financereports::reports.aging', compact('locations', 'as_at', 'location_id', 'report', 'title'));
    }

    private function paymentMovementReport(Request $request, string $contactType, string $title, string $view)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->paymentMovement($business_id, $contactType, $start, $end, $location_id);

        return view('financereports::reports.payment_movement', compact('locations', 'start', 'end', 'location_id', 'report', 'title'));
    }

    private function summaryReport(Request $request, string $contactType, string $title, string $view)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->receivablePayableSummary($business_id, $contactType, $as_at, $location_id);

        return view('financereports::reports.receivable_payable_summary', compact('locations', 'as_at', 'location_id', 'report', 'title'));
    }

    private function statementReport(Request $request, string $contactType, string $title, string $view)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $contacts = $this->service->contactsForDropdown($business_id, $contactType);
        $contact_id = (int) $request->input('contact_id');
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $contact_id > 0 ? $this->service->contactStatement($business_id, $contactType, $contact_id, $start, $end, $location_id) : null;

        return view('financereports::reports.contact_statement', compact('locations', 'contacts', 'contact_id', 'start', 'end', 'location_id', 'report', 'title'));
    }


    public function cashFlowStatement(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->cashFlowStatement($business_id, $start, $end, $location_id);

        return view('financereports::reports.cash_flow_statement', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function cashPositionReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->positionReport($business_id, $as_at, $location_id, ['cash'], 'Cash Position Report - New');

        return view('financereports::reports.position_report', compact('locations', 'as_at', 'location_id', 'report') + ['title' => 'Cash Position Report - New']);
    }

    public function bankPositionReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->positionReport($business_id, $as_at, $location_id, ['bank'], 'Bank Position Report - New');

        return view('financereports::reports.position_report', compact('locations', 'as_at', 'location_id', 'report') + ['title' => 'Bank Position Report - New']);
    }

    public function bankReconciliation(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->bankReconciliation($business_id, $as_at, $location_id);

        return view('financereports::reports.bank_reconciliation', compact('locations', 'as_at', 'location_id', 'report'));
    }

    public function chequeRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->chequeRegister($business_id, $start, $end, $location_id, false);

        return view('financereports::reports.cheque_register', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Cheque Register - New']);
    }

    public function postDatedChequeRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->addMonth()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->chequeRegister($business_id, $start, $end, $location_id, true);

        return view('financereports::reports.cheque_register', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Post-Dated Cheque Register - New']);
    }

    public function cashMovementAnalysis(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->cashMovementAnalysis($business_id, $start, $end, $location_id);

        return view('financereports::reports.cash_movement_analysis', compact('locations', 'start', 'end', 'location_id', 'report'));
    }



    public function auditTrail(Request $request)
    {
        return $this->auditReport($request, 'Audit Trail - New', 'auditTrail');
    }

    public function transactionHistory(Request $request)
    {
        return $this->auditReport($request, 'Transaction History - New', 'transactionHistory');
    }

    public function userFinancialActivity(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->userFinancialActivity($business_id, $start, $end, $location_id);

        return view('financereports::reports.user_financial_activity', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function deletedTransactions(Request $request)
    {
        return $this->auditReport($request, 'Deleted Transactions - New', 'deletedTransactions');
    }

    public function editedTransactions(Request $request)
    {
        return $this->auditReport($request, 'Edited Transactions - New', 'editedTransactions');
    }

    public function voucherApprovalHistory(Request $request)
    {
        return $this->auditReport($request, 'Voucher Approval History - New', 'voucherApprovalHistory');
    }

    public function exceptionReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->exceptionReport($business_id, $as_at, $location_id);

        return view('financereports::reports.exception_report', compact('locations', 'as_at', 'location_id', 'report'));
    }

    public function financialLogViewer(Request $request)
    {
        return $this->auditReport($request, 'Financial Log Viewer - New', 'financialLogViewer');
    }



    public function fixedAssetDashboard(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->fixedAssetDashboard($business_id, $as_at, $location_id);

        return view('financereports::reports.fixed_asset_dashboard', compact('locations', 'as_at', 'location_id', 'report'));
    }

    public function fixedAssetRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->fixedAssetRegister($business_id, $as_at, $location_id);

        return view('financereports::reports.fixed_asset_register', compact('locations', 'as_at', 'location_id', 'report'));
    }

    public function depreciationRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfYear()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->depreciationRegister($business_id, $start, $end, $location_id);

        return view('financereports::reports.depreciation_register', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function assetMovementRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->assetMovementRegister($business_id, $start, $end, $location_id);

        return view('financereports::reports.asset_movement_register', compact('locations', 'start', 'end', 'location_id', 'report'));
    }

    public function assetTransferReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->assetMovementRegister($business_id, $start, $end, $location_id, 'transfer');

        return view('financereports::reports.asset_movement_register', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Asset Transfer Report - New']);
    }

    public function assetDisposalRegister(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfYear()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->assetMovementRegister($business_id, $start, $end, $location_id, 'disposal');

        return view('financereports::reports.asset_movement_register', compact('locations', 'start', 'end', 'location_id', 'report') + ['title' => 'Asset Disposal Register - New']);
    }

    public function assetCategorySummary(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->assetCategorySummary($business_id, $as_at, $location_id);

        return view('financereports::reports.asset_category_summary', compact('locations', 'as_at', 'location_id', 'report'));
    }

    public function assetValuationReport(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $as_at = $request->input('as_at') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->assetValuationReport($business_id, $as_at, $location_id);

        return view('financereports::reports.asset_valuation_report', compact('locations', 'as_at', 'location_id', 'report'));
    }


    public function executiveBiDashboard(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $dashboard = $this->engine->executiveDashboard($context);
        $status = $this->engine->status($context);

        return view('financereports::reports.executive_bi_dashboard', compact('locations', 'context', 'dashboard', 'status'));
    }

    public function reportEngineStatus(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $status = $this->engine->status($context);

        return view('financereports::reports.engine.status', compact('locations', 'context', 'status'));
    }



    public function enterpriseCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $center = $this->engine->enterpriseCenter($context);

        return view('financereports::reports.enterprise_center', compact('locations', 'context', 'center'));
    }

    public function cashFlowForecast(Request $request)
    {
        return $this->forecastReport($request, 'cash_flow', 'Cash Flow Forecast - New');
    }

    public function revenueForecast(Request $request)
    {
        return $this->forecastReport($request, 'revenue', 'Revenue Forecast - New');
    }

    public function expenseForecast(Request $request)
    {
        return $this->forecastReport($request, 'expense', 'Expense Forecast - New');
    }

    public function profitForecast(Request $request)
    {
        return $this->forecastReport($request, 'profit', 'Profit Forecast - New');
    }

    public function consolidationCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $report = $this->engine->consolidationCenter($context);

        return view('financereports::reports.consolidation_center', compact('locations', 'context', 'report'));
    }

    public function performanceCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $report = $this->engine->performanceCenter($context);

        return view('financereports::reports.performance_center', compact('locations', 'context', 'report'));
    }

    private function forecastReport(Request $request, string $type, string $title)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $months = (int) $request->input('months', 6);
        $months = max(1, min($months, 24));
        $report = $this->engine->forecast($context, $type, $months);

        return view('financereports::reports.forecast', compact('locations', 'context', 'report', 'title', 'type', 'months'));
    }

    private function auditReport(Request $request, string $title, string $method)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $start = $request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d');
        $end = $request->input('end_date') ?: now()->format('Y-m-d');
        $location_id = $this->locationId($request);
        $report = $this->service->{$method}($business_id, $start, $end, $location_id);

        return view('financereports::reports.audit_table', compact('locations', 'start', 'end', 'location_id', 'report', 'title'));
    }


    public function standaloneAudit(Request $request)
    {
        $audit = app(FinanceReportsStandaloneAuditService::class);
        $checklist = $audit->checklist();

        return view('financereports::reports.rc2.standalone_audit', compact('checklist'));
    }

    public function exportCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $formats = app(FinanceReportExportService::class)->supportedFormats();

        return view('financereports::reports.rc2.export_center', compact('locations', 'formats'));
    }

    public function printLayoutCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $header = app(FinanceReportPrintService::class)->header('Finance Reports', [
            'branch' => $request->input('location_id') ?: 'Consolidated',
            'period' => trim(($request->input('start_date') ?: now()->startOfMonth()->format('Y-m-d')) . ' - ' . ($request->input('end_date') ?: now()->format('Y-m-d'))),
        ]);

        return view('financereports::reports.rc2.print_layout_center', compact('locations', 'header'));
    }

    public function drilldownCenter(Request $request)
    {
        $trail = app(FinanceReportDrillDownService::class)->trail($request->all());

        return view('financereports::reports.rc2.drilldown_center', compact('trail'));
    }


    public function financialPack(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $pack = app(FinancialPackService::class)->build($this->service, $business_id, $context);

        return view('financereports::reports.v1.financial_pack', compact('locations', 'context', 'pack'));
    }

    public function reportScheduler(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $schedules = app(FinanceReportSchedulerService::class)->defaultSchedules();

        return view('financereports::reports.v1.report_scheduler', compact('locations', 'context', 'schedules'));
    }

    public function executiveKpiCenter(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $kpis = app(ExecutiveKpiService::class)->calculate($this->service, $business_id, $context);

        return view('financereports::reports.v1.executive_kpi_center', compact('locations', 'context', 'kpis'));
    }

    public function calculationVerification(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $verification = app(ProductionReadinessService::class)->calculationChecklist();

        return view('financereports::reports.v1.calculation_verification', compact('locations', 'context', 'verification'));
    }

    public function productionReadiness(Request $request)
    {
        $readiness = app(ProductionReadinessService::class)->readinessChecklist();

        return view('financereports::reports.v1.production_readiness', compact('readiness'));
    }


    public function financialIntelligence(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $intelligence = app(\Modules\FinanceReports\Services\Intelligence\FinancialIntelligenceService::class)->summary($this->service, $context);

        return view('financereports::reports.v2.financial_intelligence', compact('locations', 'context', 'intelligence'));
    }

    public function cfoDashboard(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $dashboard = app(\Modules\FinanceReports\Services\Intelligence\FinancialIntelligenceService::class)->cfoDashboard($this->service, $context);

        return view('financereports::reports.v2.cfo_dashboard', compact('locations', 'context', 'dashboard'));
    }

    public function financialHealthScore(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $intelligence = app(\Modules\FinanceReports\Services\Intelligence\FinancialIntelligenceService::class)->summary($this->service, $context);

        return view('financereports::reports.v2.financial_health_score', compact('locations', 'context', 'intelligence'));
    }

    public function scenarioAnalysis(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $revenue_change = (float) $request->input('revenue_change', 0);
        $expense_change = (float) $request->input('expense_change', 0);
        $scenario = app(\Modules\FinanceReports\Services\Intelligence\FinancialIntelligenceService::class)->scenario($this->service, $context, $revenue_change, $expense_change);

        return view('financereports::reports.v2.scenario_analysis', compact('locations', 'context', 'scenario', 'revenue_change', 'expense_change'));
    }

    public function boardPack(Request $request)
    {
        $business_id = $this->businessId($request);
        $locations = $this->service->locations($business_id);
        $context = $this->engine->contextFromRequest($request, $business_id);
        $pack = app(FinancialPackService::class)->build($this->service, $business_id, $context);
        $intelligence = app(\Modules\FinanceReports\Services\Intelligence\FinancialIntelligenceService::class)->summary($this->service, $context);

        return view('financereports::reports.v2.board_pack', compact('locations', 'context', 'pack', 'intelligence'));
    }



    public function enterpriseDataHub(Request $request)
    {
        $summary = app(\Modules\FinanceReports\Services\Enterprise\EnterpriseDataHubService::class)->summary();

        return view('financereports::reports.v3.enterprise_data_hub', compact('summary'));
    }

    public function crossModuleFinancialIntelligence(Request $request)
    {
        $service = app(\Modules\FinanceReports\Services\Enterprise\CrossModuleKpiService::class);
        $kpis = $service->dashboard();
        $rules = $service->insightRules();

        return view('financereports::reports.v3.cross_module_intelligence', compact('kpis', 'rules'));
    }

    public function enterpriseDashboardBuilder(Request $request)
    {
        return view('financereports::reports.v3.dashboard_builder');
    }

    public function enterpriseReportBuilder(Request $request)
    {
        $service = app(\Modules\FinanceReports\Services\Enterprise\ReportBuilderService::class);
        $fields = $service->fields();
        $templates = $service->templates();

        return view('financereports::reports.v3.report_builder', compact('fields', 'templates'));
    }

    public function financialWorkspace(Request $request)
    {
        $workspace = app(\Modules\FinanceReports\Services\Enterprise\WorkspaceService::class)->workspace();

        return view('financereports::reports.v3.financial_workspace', compact('workspace'));
    }

    public function enterpriseReportScheduler(Request $request)
    {
        return view('financereports::reports.v3.enterprise_scheduler');
    }

    public function enterprisePlatformAudit(Request $request)
    {
        return view('financereports::reports.v3.enterprise_platform_audit');
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
    }

    private function locationId(Request $request)
    {
        $location_id = $request->input('location_id');
        return empty($location_id) || $location_id === 'all' ? null : $location_id;
    }
}
