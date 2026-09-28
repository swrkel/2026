<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Entities\Customer;
use Modules\Customers\Services\CustomerService;

/**
 * RC10 compatibility layer for legacy customer URLs that previously lived in Contacts.
 *
 * The goal is to keep old bookmarks/AJAX calls from breaking while forcing all
 * customer traffic into module-owned Customers routes/controllers. Supplier-only
 * URLs remain in Contacts and are not touched here.
 */
class CustomerCompatibilityController extends Controller
{
    /** @var CustomerService */
    protected $customers;

    public function __construct(CustomerService $customers)
    {
        $this->customers = $customers;
    }

    public function register()
    {
        return redirect()->route('customers.index');
    }

    public function create()
    {
        return redirect()->route('customers.create');
    }

    public function import()
    {
        return redirect()->route('customers.import');
    }

    public function payments()
    {
        return redirect()->route('customers.reports.payments');
    }

    public function ledger(Request $request)
    {
        $id = $request->get('contact_id') ?: $request->get('customer_id') ?: $request->get('id');

        if (!empty($id)) {
            return redirect()->route('customers.ledger', ['id' => $id] + $request->query());
        }

        return redirect()->route('customers.reports.ledger', $request->query());
    }

    public function balanceDetails($id)
    {
        return redirect()->route('customers.balance', ['id' => $id]);
    }

    public function listSecurityDeposit($id)
    {
        return redirect()->route('customers.deposits.security', ['id' => $id]);
    }

    public function toggleActivate($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');
        $customer = $this->customers->findCustomer($businessId, (int) $id);
        $customer->active = (int) $customer->active === 1 ? 0 : 1;
        $customer->save();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => 1,
                'msg' => $customer->active ? 'Customer activated successfully.' : 'Customer deactivated successfully.',
                'active' => (int) $customer->active,
            ]);
        }

        return redirect()->route('customers.index')->with('status', [
            'success' => 1,
            'msg' => $customer->active ? 'Customer activated successfully.' : 'Customer deactivated successfully.',
        ]);
    }

    public function getSubCustomers(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $parentId = (int) ($request->get('parent_id') ?: $request->get('contact_id') ?: 0);

        $rows = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->when($parentId > 0, function ($query) use ($parentId) {
                $query->where('sub_customer_id', $parentId)
                    ->orWhere('parent_customer_id', $parentId);
            })
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'contact_id', 'mobile']);

        return response()->json($rows);
    }

    public function quickAddSubCustomer(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'parent_id' => 'nullable|integer',
            'sub_customer_id' => 'nullable|integer',
        ]);

        $data['customer_passcode'] = $this->customers->generateUniquePasscode($businessId);
        $customer = $this->customers->createCustomer($data, $businessId, $userId);

        $parentId = $request->get('parent_id') ?: $request->get('sub_customer_id');
        if (!empty($parentId)) {
            if (\Schema::hasColumn('contacts', 'sub_customer_id')) {
                $customer->sub_customer_id = (int) $parentId;
            }
            if (\Schema::hasColumn('contacts', 'parent_customer_id')) {
                $customer->parent_customer_id = (int) $parentId;
            }
            $customer->save();
        }

        return response()->json([
            'success' => 1,
            'msg' => 'Sub customer added successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact_id' => $customer->contact_id,
                'mobile' => $customer->mobile,
            ],
        ]);
    }

    public function importBalance()
    {
        return redirect()->route('customers.master.opening_balances.index');
    }

    public function postImportBalance()
    {
        return redirect()->route('customers.master.opening_balances.index')->with('status', [
            'success' => 1,
            'msg' => 'Customer opening balance import is now handled inside Customers > Master Data > Opening Balances.',
        ]);
    }

    public function customerLoans(Request $request)
    {
        $id = $request->get('contact_id') ?: $request->get('customer_id') ?: $request->get('id');

        if (!empty($id)) {
            return redirect()->route('customers.loans.create', ['id' => $id] + $request->query());
        }

        return redirect()->route('customers.reports.transactions', $request->query());
    }

    public function customerLoansList(Request $request)
    {
        return redirect()->route('customers.reports.transactions', $request->query());
    }

    public function customerLoanView(Request $request)
    {
        $id = $request->get('contact_id') ?: $request->get('customer_id') ?: $request->get('id');

        if (!empty($id)) {
            return redirect()->route('customers.loans.create', ['id' => $id] + $request->query());
        }

        return redirect()->route('customers.reports.transactions', $request->query());
    }

    public function getOutstandingFilters(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $customers = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(250)
            ->get(['id', 'name', 'contact_id', 'mobile']);

        return response()->json([
            'success' => 1,
            'customers' => $customers,
            'customer_count' => $customers->count(),
            'module' => 'Customers',
        ]);
    }

    public function issuedPaymentDetails(Request $request)
    {
        return redirect()->route('customers.reports.payments', $request->query());
    }

    public function returnedCheques(Request $request)
    {
        return redirect()->route('customers.reports.payments', $request->query());
    }

    public function checkContactId(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $contactId = trim((string) ($request->get('contact_id') ?: $request->get('customer_code') ?: ''));

        if ($contactId === '') {
            return response()->json(['success' => 1, 'available' => true]);
        }

        $exists = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('contact_id', $contactId)
            ->whereNull('deleted_at')
            ->exists();

        return response()->json([
            'success' => 1,
            'available' => !$exists,
            'exists' => $exists,
            'msg' => $exists ? 'Customer code already exists.' : 'Customer code is available.',
        ]);
    }

    public function search(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $term = trim((string) ($request->get('q') ?: $request->get('term') ?: $request->get('search') ?: ''));

        $rows = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', "%{$term}%")
                        ->orWhere('contact_id', 'like', "%{$term}%")
                        ->orWhere('mobile', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'contact_id', 'mobile', 'email']);

        $results = $rows->map(function ($customer) {
            $label = trim(($customer->contact_id ? $customer->contact_id . ' - ' : '') . $customer->name . ($customer->mobile ? ' (' . $customer->mobile . ')' : ''));

            return [
                'id' => $customer->id,
                'text' => $label,
                'name' => $customer->name,
                'contact_id' => $customer->contact_id,
                'mobile' => $customer->mobile,
                'email' => $customer->email,
            ];
        })->values();

        return response()->json(['results' => $results, 'items' => $results]);
    }

    public function getContacts(Request $request)
    {
        return $this->search($request);
    }

    public function checkMobile(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $mobile = trim((string) $request->get('mobile'));
        $excludeId = (int) ($request->get('id') ?: $request->get('customer_id') ?: 0);

        if ($mobile === '') {
            return response()->json(['success' => 1, 'exists' => false, 'available' => true]);
        }

        $query = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('mobile', $mobile)
            ->whereNull('deleted_at');

        if ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        $exists = $query->exists();

        return response()->json([
            'success' => 1,
            'exists' => $exists,
            'available' => ! $exists,
            'msg' => $exists ? 'Mobile number already exists for another customer.' : 'Mobile number is available.',
        ]);
    }

    public function postCustomersApi(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'credit_limit' => 'nullable',
            'address_line_1' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
        ]);

        $customer = $this->customers->createCustomer($data, $businessId, $userId);

        return response()->json([
            'success' => 1,
            'msg' => 'Customer added successfully.',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact_id' => $customer->contact_id,
                'mobile' => $customer->mobile,
                'email' => $customer->email,
            ],
        ]);
    }

}
