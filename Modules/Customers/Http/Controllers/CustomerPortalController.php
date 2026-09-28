<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Services\CustomerLedgerService;

class CustomerPortalController extends Controller
{
    protected function businessId(Request $request): int
    {
        $businessId = (int) $request->session()->get('user.business_id');

        if ($businessId > 0) {
            return $businessId;
        }

        if (Schema::hasTable('business')) {
            return (int) DB::table('business')->orderBy('id')->value('id');
        }

        return 0;
    }

    protected function loggedCustomer(Request $request): ?Customer
    {
        $businessId = $this->businessId($request);
        $customerId = (int) $request->session()->get('distribution_dealer_customer_id');

        if (empty($businessId) || empty($customerId)) {
            return null;
        }

        return Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->find($customerId);
    }

    public function showLogin()
    {
        return view('customers::portal.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'passcode' => 'required|digits:4',
        ]);

        $businessId = $this->businessId($request);

        $customer = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->where('customer_passcode', $data['passcode'])
            ->first();

        if (empty($customer)) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Invalid Distribution Dealer passcode.',
            ]);
        }

        $request->session()->put('distribution_dealer_customer_id', (int) $customer->id);
        $request->session()->put('distribution_dealer_customer_name', $customer->name);
        $request->session()->put('distribution_dealer_business_id', (int) $businessId);

        return redirect()->route('customers.portal.dashboard');
    }

    public function dashboard(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);

        return view('customers::portal.dashboard', compact('customer', 'summary'));
    }

    public function ledger(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalStatementRows($businessId, (int) $customer->id, $request->from, $request->to, 1000);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.ledger', compact('customer', 'rows', 'summary'));
    }

    public function statement(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalStatementRows($businessId, (int) $customer->id, $request->from, $request->to, 1500);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.statement', compact('customer', 'rows', 'summary'));
    }

    public function printStatement(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalStatementRows($businessId, (int) $customer->id, $request->from, $request->to, 1500);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.statement_print', compact('customer', 'rows', 'summary'));
    }

    public function exportStatement(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalStatementRows($businessId, (int) $customer->id, $request->from, $request->to, 5000);

        $filename = 'customer_statement_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $customer->contact_id ?: $customer->id) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Transaction Date', 'System Entered Date & Time', 'Reference', 'Description', 'Debit', 'Credit', 'Balance', 'Status']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->transaction_date,
                    $row->system_datetime,
                    $row->reference,
                    $row->description,
                    number_format((float) $row->debit, 2, '.', ''),
                    number_format((float) $row->credit, 2, '.', ''),
                    number_format((float) $row->balance, 2, '.', ''),
                    $row->status,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function invoices(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalInvoices($businessId, (int) $customer->id, 1000);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.invoices', compact('customer', 'rows', 'summary'));
    }

    public function payments(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalPayments($businessId, (int) $customer->id, 1000);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.payments', compact('customer', 'rows', 'summary'));
    }



    public function orders(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalOrders($businessId, (int) $customer->id, 1000);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);

        return view('customers::portal.orders', compact('customer', 'rows', 'summary'));
    }

    public function outstanding(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $rows = $ledgerService->portalOutstandingInvoices($businessId, (int) $customer->id, 1000);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);

        return view('customers::portal.outstanding', compact('customer', 'rows', 'summary'));
    }

    public function profile(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalSummary($businessId, (int) $customer->id);

        return view('customers::portal.profile', compact('customer', 'summary'));
    }


    public function notifications(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $rows = $ledgerService->portalNotifications($businessId, (int) $customer->id, 100);

        return view('customers::portal.notifications', compact('customer', 'summary', 'rows'));
    }

    public function announcements(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $rows = $ledgerService->portalAnnouncements($businessId, (int) $customer->id, 100);

        return view('customers::portal.announcements', compact('customer', 'summary', 'rows'));
    }

    public function messages(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $rows = $ledgerService->portalMessages($businessId, (int) $customer->id, 100);

        return view('customers::portal.messages', compact('customer', 'summary', 'rows'));
    }

    public function documents(Request $request, CustomerLedgerService $ledgerService)
    {
        $customer = $this->loggedCustomer($request);
        if (empty($customer)) {
            return redirect()->route('customers.portal.login');
        }
        $businessId = $this->businessId($request);
        $summary = $ledgerService->portalCreditSummary($businessId, (int) $customer->id);
        $rows = $ledgerService->portalDocuments($businessId, (int) $customer->id, 100);

        return view('customers::portal.documents', compact('customer', 'summary', 'rows'));
    }

    public function logout(Request $request)
    {
        $request->session()->forget('distribution_dealer_customer_id');
        $request->session()->forget('distribution_dealer_customer_name');
        $request->session()->forget('distribution_dealer_business_id');

        return redirect()->route('customers.portal.login')->with('status', [
            'success' => 1,
            'msg' => 'Logged out successfully.',
        ]);
    }
}
