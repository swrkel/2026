<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerService;
use Modules\Customers\Services\CustomerPermissionService;
use App\BusinessLocation;
use App\ContactGroup;
use App\Contact;
use App\User;
use Illuminate\Support\Facades\Schema;

class CustomerController extends Controller
{
    protected $customerService;
    protected $permissionService;

    public function __construct(CustomerService $customerService, CustomerPermissionService $permissionService)
    {
        $this->customerService = $customerService;
        $this->permissionService = $permissionService;
    }

    public function index(Request $request)
    {
        // The route already runs customers.access:view middleware. Do not repeat
        // the same subscription and permission checks inside the controller.
        if ($request->ajax()) {
            $businessId = (int) $request->session()->get('user.business_id');
            $page = $this->customerService->registerPage($businessId, $request->all());

            $data = [];
            foreach ($page['rows'] as $row) {
                $balanceDeferred = !isset($row->total_due) || $row->total_due === null;
                $balance = $balanceDeferred ? null : (float) $row->total_due;
                $data[] = [
                    'id' => (int) $row->id,
                    'action' => view('customers::partials.actions', compact('row', 'balanceDeferred'))->render(),
                    'contact_id' => e($row->contact_id ?? ''),
                    'name' => e($row->name ?? ''),
                    'mobile' => e($row->mobile ?? ''),
                    'email' => e($row->email ?? ''),
                    'credit_limit' => number_format((float) ($row->credit_limit ?? 0), 2),
                    'total_due' => $balanceDeferred
                        ? '<span class="customer-total-due customer-total-due-loading" data-customer-id="' . (int) $row->id . '"><i class="fa fa-spinner fa-spin"></i></span>'
                        : '<span class="display_currency customer-total-due" data-customer-id="' . (int) $row->id . '" data-currency_symbol="true" data-orig-value="' . $balance . '">' . number_format($balance, 2) . '</span>',
                    'active' => (int) ($row->active ?? 1) === 1
                        ? '<span class="label label-success">Active</span>'
                        : '<span class="label label-danger">Inactive</span>',
                ];
            }

            return response()->json([
                'draw' => $page['draw'],
                'recordsTotal' => $page['recordsTotal'],
                'recordsFiltered' => $page['recordsFiltered'],
                'data' => $data,
            ]);
        }

        return view('customers::index');
    }

    public function registerTotalDue(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        return response()->json([
            'success' => 1,
            'total_due' => $this->customerService->overallTotalDue($businessId),
        ]);
    }


    /**
     * Deferred balance endpoint for the Customer Register.
     *
     * The master customer rows should render immediately. Receivable calculation
     * is intentionally separated into this request so large payment/ledger
     * histories never block the first paint of the register table.
     */
    public function registerPageBalances(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rawIds = (string) $request->query('customer_ids', '');
        $ids = array_values(array_unique(array_filter(array_map('intval', preg_split('/[^0-9]+/', $rawIds)))));
        $ids = array_slice($ids, 0, 500);

        return response()->json([
            'success' => 1,
            'balances' => $this->customerService->registerBalancesForIds($businessId, $ids),
        ]);
    }

    public function create()
    {
        $this->permissionService->authorize('create');

        $businessId = (int) request()->session()->get('user.business_id');
        $generatedPasscode = $this->customerService->generateUniquePasscode($businessId);
        $formData = $this->customerFormData($businessId);

        // MA-002 (S-622): same for Add, which uses the same popup.
        if (request()->ajax()) {
            return view('customers::customers.modal_form', array_merge($formData, compact('generatedPasscode'), ['customer' => null]));
        }

        return view('customers::customers.create', array_merge($formData, compact('generatedPasscode')));
    }

    public function store(Request $request)
    {
        $this->permissionService->authorize('create');

        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');

        $data = $this->validatedCustomerData($request);
        $this->validatePasscodeIsUnique($businessId, $data['customer_passcode'] ?? null);
        $customer = $this->customerService->createCustomer($data, $businessId, $userId);

        $output = [
            'success' => 1,
            'msg' => 'Successfully Saved',
            'data' => $customer,
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.index')->with('status', $output);
    }

    public function show($id)
    {
        $this->permissionService->authorize('view');

        $businessId = (int) request()->session()->get('user.business_id');
        $customer = $this->customerService->findCustomerForView($businessId, (int) $id);
        $formData = $this->customerFormData($businessId);
        $canEdit = $this->permissionService->allows('edit');

        return view('customers::show', array_merge($formData, compact('customer', 'canEdit')));
    }

    public function edit($id)
    {
        $this->permissionService->authorize('edit');

        $businessId = (int) request()->session()->get('user.business_id');
        $customer = $this->customerService->findCustomer($businessId, (int) $id);

        $formData = $this->customerFormData($businessId);
        $generatedPasscode = preg_match('/^\d{4}$/', (string) ($customer->customer_passcode ?? ''))
            ? (string) $customer->customer_passcode
            : $this->customerService->generateUniquePasscode($businessId, (int) $customer->id);

        /*
         * MA-002 (S-622): serve a BARE view to the popup.
         *
         * customers.edit extends layouts.app, so an ajax request received an
         * entire page - all 71 script tags from the layout, jQuery among them.
         * Injecting that re-evaluates jQuery, wiping every plugin already
         * registered, and the resulting error abandoned the rest of the
         * injected script - including the select2 initialisation.
         */
        if (request()->ajax()) {
            return view('customers::customers.modal_form', array_merge($formData, compact('customer', 'generatedPasscode')));
        }

        return view('customers::customers.edit', array_merge($formData, compact('customer', 'generatedPasscode')));
    }

    public function update(Request $request, $id)
    {
        $this->permissionService->authorize('edit');

        $businessId = (int) $request->session()->get('user.business_id');
        $customer = $this->customerService->findCustomer($businessId, (int) $id);

        $data = $this->validatedCustomerData($request, true);

        if (empty($data['customer_passcode'])) {
            $data['customer_passcode'] = preg_match('/^\d{4}$/', (string) ($customer->customer_passcode ?? ''))
                ? (string) $customer->customer_passcode
                : $this->customerService->generateUniquePasscode($businessId, (int) $customer->id);
        }

        $this->validatePasscodeIsUnique($businessId, $data['customer_passcode'] ?? null, (int) $customer->id);
        $this->customerService->updateCustomer($customer, $data, $businessId);

        $output = [
            'success' => 1,
            'msg' => 'Changes Updated Successfully.',
            'data' => $customer->fresh(),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->route('customers.index')->with('status', $output);
    }

    public function destroy($id)
    {
        $this->permissionService->authorize('delete');

        $businessId = (int) request()->session()->get('user.business_id');
        $customer = $this->customerService->findCustomer($businessId, (int) $id);
        $customer->delete();
        $this->customerService->clearRegisterCountCache($businessId);

        return redirect()->route('customers.index')->with('status', [
            'success' => 1,
            'msg' => 'Customer deleted successfully.',
        ]);
    }


    public function import()
    {
        $this->permissionService->authorize('create');
        return view('customers::customers.import');
    }

    public function postImport(Request $request)
    {
        $this->permissionService->authorize('create');

        $request->validate([
            'contacts_csv' => 'required|file|mimes:csv,txt',
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $userId = (int) $request->session()->get('user.id');
        $file = $request->file('contacts_csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $created = 0;
        $skipped = 0;

        if ($header === false) {
            return redirect()->route('customers.import')->with('status', ['success' => 0, 'msg' => 'Import file is empty.']);
        }

        $header = array_map(function ($value) {
            return strtolower(trim((string) $value));
        }, $header);

        while (($row = fgetcsv($handle)) !== false) {
            $payload = array_combine($header, array_pad($row, count($header), null));
            $name = trim((string) ($payload['name'] ?? $payload['customer_name'] ?? ''));

            if ($name === '') {
                $skipped++;
                continue;
            }

            $data = [
                'name' => $name,
                'mobile' => $payload['mobile'] ?? $payload['phone'] ?? null,
                'email' => $payload['email'] ?? null,
                'credit_limit' => $payload['credit_limit'] ?? 0,
                'address_line_1' => $payload['address_line_1'] ?? $payload['address'] ?? null,
                'city' => $payload['city'] ?? null,
                // Registration fields - see the validation rules for why the
                // date of birth is stored as three separate values.
                'dob_day' => $payload['dob_day'] ?? null,
                'dob_month' => $payload['dob_month'] ?? null,
                'dob_year' => $payload['dob_year'] ?? null,
                'living_district' => $payload['living_district'] ?? null,
                'town' => $payload['town'] ?? null,
                'active' => 1,
                'customer_passcode' => $this->customerService->generateUniquePasscode($businessId),
            ];

            $this->customerService->createCustomer($data, $businessId, $userId);
            $created++;
        }

        fclose($handle);

        return redirect()->route('customers.index')->with('status', [
            'success' => 1,
            'msg' => "Customer import completed. Created: {$created}. Skipped: {$skipped}.",
        ]);
    }

    public function export(Request $request)
    {
        $this->permissionService->authorize('export');

        $businessId = (int) $request->session()->get('user.business_id');
        $filename = 'customers_export_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($businessId) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Customer Code', 'Name', 'Mobile', 'Email', 'Credit Limit', 'City', 'Active']);

            $this->customerService->query($businessId)
                ->orderBy('name')
                ->chunk(500, function ($customers) use ($out) {
                    foreach ($customers as $customer) {
                        fputcsv($out, [
                            $customer->contact_id,
                            $customer->name,
                            $customer->mobile,
                            $customer->email,
                            $customer->credit_limit,
                            $customer->city,
                            (int) $customer->active === 1 ? 'Yes' : 'No',
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function validatedCustomerData(Request $request, bool $isUpdate = false): array
    {
        $passcodeRule = $isUpdate ? 'nullable|digits:4' : 'required|digits:4';

        return $request->validate([
            'type' => 'nullable|in:customer,both',
            'name' => 'required|string|max:255',
            'supplier_business_name' => 'nullable|string|max:255',
            'contact_id' => 'nullable|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'alternate_number' => 'nullable|string|max:50',
            'landline' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'vat_number' => 'nullable|string|max:100',
            'customer_group_id' => 'nullable',
            'business_location_id' => 'nullable',
            'pay_term_number' => 'nullable',
            'pay_term_type' => 'nullable|string|max:50',
            'whatsapp_number' => 'nullable|string|max:50',
            'assigned_to' => 'nullable',
            'vehicle_no' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'address_2' => 'nullable|string|max:500',
            'address_3' => 'nullable|string|max:500',
            'sub_customers' => 'nullable',
            'sub_customers.*' => 'nullable',
            'credit_limit' => 'nullable',
            'opening_balance' => 'nullable',
            'transaction_date' => 'nullable|string|max:50',
            'address_line_1' => 'nullable|string|max:500',
            'address_line_2' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',

            /*
             * Registration fields. The date of birth is validated as three
             * separate values because the YEAR is optional - most customers give
             * only the day and month, and a single date rule would force a year
             * that was never supplied.
             *
             * 29 February is accepted: day 29 with month 2 is a real birthday,
             * and rejecting it would tell roughly one customer in 1,500 that
             * their birthday is invalid. Greetings in non-leap years fall back
             * to 28 February, which is a display decision, not a storage one.
             */
            'dob_day' => 'nullable|integer|min:1|max:31',
            'dob_month' => 'nullable|integer|min:1|max:12',
            'dob_year' => 'nullable|integer|min:1900|max:' . date('Y'),
            'living_district' => 'nullable|string|max:100',
            'town' => 'nullable|string|max:150',

            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:30',
            'active' => 'nullable|in:0,1',
            'should_notify' => 'nullable|in:0,1',
            'credit_notification' => 'nullable|string|max:100',
            // MA-002 (S-622): Yes/No, so only 0 or 1 are accepted.
            'need_to_send_sms' => 'nullable|in:0,1',
            'manual_bill_settlement' => 'nullable|in:0,1',
            'sub_customer' => 'nullable|in:0,1',
            'notification_contacts' => 'nullable|string|max:1000',
            'customer_passcode' => $passcodeRule,
        ]);
    }


    protected function customerFormData(int $businessId): array
    {
        $groupOptions = ContactGroup::where('business_id', $businessId)
            ->where(function ($query) {
                $query->where('type', 'customer')->orWhere('type', 'both');
            })
            ->pluck('name', 'id')
            ->toArray();

        $businessLocations = BusinessLocation::where('business_id', $businessId)
            ->pluck('name', 'id')
            ->toArray();

        $customers = Contact::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        $userGroups = [];
        if (class_exists(User::class)) {
            $userGroups = User::where('business_id', $businessId)
                ->selectRaw("id, CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) as full_name")
                ->orderBy('first_name')
                ->pluck('full_name', 'id')
                ->toArray();
        }

        return [
            'groupOptions' => $groupOptions,
            'businessLocations' => $businessLocations,
            'customers' => $customers,
            'userGroups' => $userGroups,
            'typeOptions' => ['customer' => 'Customer', 'both' => 'Both Supplier & Customer'],
            'statusOptions' => ['1' => 'Active', '0' => 'Inactive'],
            'payTermTypeOptions' => ['days' => 'Days', 'months' => 'Months'],
        ];
    }

    protected function validatePasscodeIsUnique(int $businessId, ?string $passcode, ?int $excludeId = null): void
    {
        if (empty($passcode) || !Schema::hasColumn('contacts', 'customer_passcode')) {
            return;
        }

        $query = \Modules\Customers\Entities\Customer::where('business_id', $businessId)
            ->where('customer_passcode', $passcode);

        if (!empty($excludeId)) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'customer_passcode' => 'This customer passcode is already used. Please use another 4 digit passcode.',
            ]);
        }
    }
}
