<?php

namespace Modules\BankingCorporateBanking\Http\Controllers;

use Illuminate\Routing\Controller;

class CorporateBankingController extends Controller
{
    private function view(string $view, array $extra = [])
    {
        return view('bankingcorporatebanking::' . $view, array_merge([
            'moduleTitle' => 'Corporate Banking',
        ], $extra));
    }

    public function dashboard() { return $this->view('dashboard.index'); }
    public function corporates() { return $this->view('corporates.index', ['pageTitle' => 'Corporate Customers']); }
    public function signatories() { return $this->view('signatories.index', ['pageTitle' => 'Signatories & Mandates']); }
    public function approvals() { return $this->view('approvals.index', ['pageTitle' => 'Maker / Checker / Approver']); }
    public function bulkPayments() { return $this->view('bulk_payments.index', ['pageTitle' => 'Bulk Payments']); }
    public function payroll() { return $this->view('payroll.index', ['pageTitle' => 'Payroll & Salary Processing']); }
    public function cashManagement() { return $this->view('cash_management.index', ['pageTitle' => 'Cash Management']); }
    public function collections() { return $this->view('collections.index', ['pageTitle' => 'Corporate Collections']); }
    public function reports() { return $this->view('reports.index', ['pageTitle' => 'Corporate Banking Reports']); }
    public function settings() { return $this->view('settings.index', ['pageTitle' => 'Corporate Banking Settings']); }
}
