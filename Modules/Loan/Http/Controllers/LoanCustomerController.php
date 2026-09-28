<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Validation\Rule;
use Modules\Loan\Entities\LoanCustomer;
use Modules\Loan\Entities\LoanOfficer;

class LoanCustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureTable();
        $business_id = $this->businessId();

        $query = LoanCustomer::forBusiness($business_id)->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('customer_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('nic', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $customers = $query->paginate(25)->appends($request->query());

        return view('loan::loan_customers.index', compact('customers'));
    }

    public function create()
    {
        $this->ensureTable();
        $this->ensureLoanOfficerTable();

        $business_id = $this->businessId();
        $customer = new LoanCustomer();
        $customer->email = 'No Email ID';
        $customer->status = 'active';
        $customer->customer_type = 'individual';
        $customer->branch_id = $this->defaultBranchId($business_id);
        $customer->branch_name = $this->branchNameById($customer->branch_id, $business_id);
        $customer->loan_officer = $this->defaultLoanOfficerName($business_id);
        $next_customer_no = $this->nextCustomerNo($business_id);
        $business_locations = $this->businessLocationOptions($business_id);
        $loan_officers = $this->loanOfficerOptions($business_id);

        return view('loan::loan_customers.create', compact('customer', 'next_customer_no', 'business_locations', 'loan_officers'));
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $business_id = $this->businessId();

        $data = $this->validatedData($request, $business_id);
        $data['business_id'] = $business_id;
        $data['created_by'] = optional(Auth::user())->id;
        $data['updated_by'] = optional(Auth::user())->id;

        if (empty($data['customer_no'])) {
            $data['customer_no'] = $this->nextCustomerNo($business_id);
        }

        $data['email'] = $this->normalizeEmail($data['email'] ?? null);
        $data = $this->syncBranchName($data, $business_id);
        $data['name'] = $this->makeFullName($data);

        try {
            $data = array_merge($data, $this->storeUploadedFiles($request, $business_id));
            $customer = LoanCustomer::create($data);

            $redirect = $request->input('submit_type') === 'save_and_new'
                ? redirect()->route('loan.customers.create')
                : redirect()->route('loan.customers.edit', $customer->id);

            return $redirect->with('status', [
                'success' => 1,
                'msg' => 'Loan customer saved successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Loan customer save failed', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Unable to save loan customer. ' . $e->getMessage(),
            ]);
        }
    }

    public function show($id)
    {
        $this->ensureTable();
        $customer = LoanCustomer::forBusiness($this->businessId())->findOrFail($id);

        return view('loan::loan_customers.show', compact('customer'));
    }

    public function edit($id)
    {
        $this->ensureTable();
        $this->ensureLoanOfficerTable();
        $business_id = $this->businessId();
        $customer = LoanCustomer::forBusiness($business_id)->findOrFail($id);
        $business_locations = $this->businessLocationOptions($business_id);
        $loan_officers = $this->loanOfficerOptions($business_id);

        return view('loan::loan_customers.edit', compact('customer', 'business_locations', 'loan_officers'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureTable();
        $business_id = $this->businessId();
        $customer = LoanCustomer::forBusiness($business_id)->findOrFail($id);

        $data = $this->validatedData($request, $business_id, $id);
        $data['email'] = $this->normalizeEmail($data['email'] ?? null);
        $data = $this->syncBranchName($data, $business_id);
        $data['name'] = $this->makeFullName($data);
        $data['updated_by'] = optional(Auth::user())->id;

        try {
            $data = array_merge($data, $this->storeUploadedFiles($request, $business_id, $customer));
            $customer->update($data);

            return redirect()->route('loan.customers.edit', $customer->id)->with('status', [
                'success' => 1,
                'msg' => 'Loan customer updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Loan customer update failed', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('status', [
                'success' => 0,
                'msg' => 'Unable to update loan customer. ' . $e->getMessage(),
            ]);
        }
    }


    public function ledger($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Customer Ledger';
        $active_tab = 'ledger';
        $records = $this->customerLedgerRecords($customer);

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function applications($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Loan Applications';
        $active_tab = 'applications';
        $records = $this->customerApplicationRecords($customer, false);

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function activeLoans($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Active Loans';
        $active_tab = 'active_loans';
        $records = $this->customerApplicationRecords($customer, true);

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function documents($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Documents';
        $active_tab = 'documents';
        $records = collect();

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function notes($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Notes';
        $active_tab = 'notes';
        $records = collect();

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function auditLog($id)
    {
        $customer = $this->findLoanCustomer($id);
        $page_title = 'Audit Log';
        $active_tab = 'audit_log';
        $records = collect();

        return view('loan::loan_customers.function_page', compact('customer', 'page_title', 'active_tab', 'records'));
    }

    public function destroy($id)
    {
        $this->ensureTable();
        $customer = LoanCustomer::forBusiness($this->businessId())->findOrFail($id);
        $customer->delete();

        return redirect()->route('loan.customers.index')->with('status', [
            'success' => 1,
            'msg' => 'Loan customer deleted successfully.',
        ]);
    }


    private function findLoanCustomer($id)
    {
        $this->ensureTable();
        return LoanCustomer::forBusiness($this->businessId())->findOrFail($id);
    }

    private function customerApplicationRecords(LoanCustomer $customer, $active_only = false)
    {
        $business_id = $this->businessId();
        $possible_tables = ['loan_applications', 'loans'];

        foreach ($possible_tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('business_id', $business_id);

            if (Schema::hasColumn($table, 'loan_customer_id')) {
                $query->where('loan_customer_id', $customer->id);
            } elseif (Schema::hasColumn($table, 'customer_id')) {
                $query->where('customer_id', $customer->id);
            } elseif (Schema::hasColumn($table, 'contact_id')) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($active_only && Schema::hasColumn($table, 'status')) {
                $query->whereIn('status', ['active', 'approved', 'disbursed', 'running', 'open']);
            }

            return $query->orderByDesc(Schema::hasColumn($table, 'id') ? 'id' : 'created_at')->limit(50)->get();
        }

        return collect();
    }

    private function customerLedgerRecords(LoanCustomer $customer)
    {
        $business_id = $this->businessId();
        $possible_tables = ['loan_customer_ledgers', 'loan_transactions', 'loan_repayments'];

        foreach ($possible_tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $query = DB::table($table)->where('business_id', $business_id);

            if (Schema::hasColumn($table, 'loan_customer_id')) {
                $query->where('loan_customer_id', $customer->id);
            } elseif (Schema::hasColumn($table, 'customer_id')) {
                $query->where('customer_id', $customer->id);
            } else {
                $query->whereRaw('1 = 0');
            }

            return $query->orderByDesc(Schema::hasColumn($table, 'id') ? 'id' : 'created_at')->limit(100)->get();
        }

        return collect();
    }

    private function validatedData(Request $request, $business_id, $ignore_id = null)
    {
        $unique_customer_no = Rule::unique('loan_customers', 'customer_no');
        if (!empty($ignore_id)) {
            $unique_customer_no = $unique_customer_no->ignore($ignore_id);
        }

        return $request->validate([
            'customer_no' => ['nullable', 'string', 'max:50', $unique_customer_no],
            'title' => ['nullable', 'string', 'max:20'],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:191'],
            'nic' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'customer_type' => ['nullable', 'string', 'max:50'],
            'risk_grade' => ['nullable', 'string', 'max:50'],
            'loan_officer' => ['nullable', 'string', 'max:100'],
            'branch_id' => ['nullable', 'integer'],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'alternate_mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:1000'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer_name' => ['nullable', 'string', 'max:150'],
            'monthly_income' => ['nullable', 'numeric'],
            'status' => ['required', 'in:active,inactive,blacklisted,deceased,closed'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'nic_front_image' => ['nullable', 'image', 'max:5120'],
            'nic_back_image' => ['nullable', 'image', 'max:5120'],
            'signature_image' => ['nullable', 'image', 'max:5120'],
        ]);
    }

    private function makeFullName(array $data)
    {
        $parts = [
            $data['first_name'] ?? '',
            $data['middle_name'] ?? '',
            $data['last_name'] ?? '',
        ];

        $name = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
        return $name ?: ($data['name'] ?? 'Loan Customer');
    }

    private function normalizeEmail($email)
    {
        $email = trim((string) $email);
        return $email === '' ? 'No Email ID' : $email;
    }

    private function storeUploadedFiles(Request $request, $business_id, LoanCustomer $customer = null)
    {
        $fields = [
            'photo' => 'photo',
            'nic_front_image' => 'nic_front_image',
            'nic_back_image' => 'nic_back_image',
            'signature_image' => 'signature_image',
        ];

        $saved = [];
        $dir = public_path('uploads/loan_customers/' . $business_id);

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        foreach ($fields as $requestField => $column) {
            if (!$request->hasFile($requestField) || !$request->file($requestField)->isValid()) {
                continue;
            }

            $file = $request->file($requestField);
            $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $filename = $requestField . '_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $extension;
            $file->move($dir, $filename);

            $relativePath = 'uploads/loan_customers/' . $business_id . '/' . $filename;

            if (!empty($customer) && !empty($customer->{$column})) {
                $old = public_path($customer->{$column});
                if (File::exists($old)) {
                    @File::delete($old);
                }
            }

            $saved[$column] = $relativePath;
        }

        return $saved;
    }

    private function syncBranchName(array $data, $business_id)
    {
        $branch_id = $data['branch_id'] ?? null;
        if (!empty($branch_id)) {
            $data['branch_name'] = $this->branchNameById($branch_id, $business_id) ?: ($data['branch_name'] ?? null);
        }

        return $data;
    }

    private function businessLocationOptions($business_id)
    {
        if (!Schema::hasTable('business_locations')) {
            return [];
        }

        $query = DB::table('business_locations')
            ->where('business_id', $business_id)
            ->orderBy('name');

        if (Schema::hasColumn('business_locations', 'is_active')) {
            $query->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            });
        }

        $permitted = $this->permittedLocationIds();
        if (is_array($permitted) && !in_array('all', $permitted)) {
            $query->whereIn('id', $permitted);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    private function permittedLocationIds()
    {
        $user = Auth::user();
        if ($user && method_exists($user, 'permitted_locations')) {
            $locations = $user->permitted_locations();
            if ($locations === 'all') {
                return ['all'];
            }
            if (is_array($locations)) {
                return $locations;
            }
        }

        $session_location = request()->session()->get('business.location_id')
            ?: request()->session()->get('user.location_id')
            ?: request()->session()->get('business_location_id');

        return !empty($session_location) ? [(int) $session_location] : ['all'];
    }

    private function defaultBranchId($business_id)
    {
        $options = $this->businessLocationOptions($business_id);
        return !empty($options) ? array_key_first($options) : null;
    }

    private function branchNameById($branch_id, $business_id)
    {
        if (empty($branch_id) || !Schema::hasTable('business_locations')) {
            return null;
        }

        return DB::table('business_locations')
            ->where('business_id', $business_id)
            ->where('id', $branch_id)
            ->value('name');
    }

    private function loanOfficerOptions($business_id)
    {
        $this->ensureLoanOfficerTable();
        return LoanOfficer::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'name')
            ->toArray();
    }

    private function defaultLoanOfficerName($business_id)
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $name = trim($user->first_name . ' ' . $user->last_name);
        if ($name === '') {
            $name = $user->username ?? $user->email ?? '';
        }

        if ($name !== '' && LoanOfficer::where('business_id', $business_id)->where('status', 'active')->where('name', $name)->exists()) {
            return $name;
        }

        if (!empty($user->id)) {
            $officer = LoanOfficer::where('business_id', $business_id)->where('status', 'active')->where('user_id', $user->id)->first();
            if ($officer) {
                return $officer->name;
            }
        }

        return null;
    }

    private function ensureLoanOfficerTable()
    {
        if (Schema::hasTable('loan_officers')) {
            return;
        }

        Schema::create('loan_officers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('name', 191);
            $table->string('email', 191)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('status', 30)->default('active')->index();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    private function nextCustomerNo($business_id)
    {
        $last = LoanCustomer::forBusiness($business_id)->orderBy('id', 'desc')->value('customer_no');
        $next = 1;

        if (!empty($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return 'LC-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    private function businessId()
    {
        return request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(Auth::user())->business_id;
    }

    private function ensureTable()
    {
        if (!Schema::hasTable('loan_customers')) {
            Schema::create('loan_customers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id')->index();
                $table->string('customer_no', 50)->nullable()->unique();
                $table->string('title', 20)->nullable();
                $table->string('first_name', 100)->nullable();
                $table->string('middle_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->string('name', 191)->index();
                $table->string('nic', 50)->nullable()->index();
                $table->date('date_of_birth')->nullable();
                $table->string('gender', 30)->nullable();
                $table->string('marital_status', 30)->nullable();
                $table->string('customer_type', 50)->nullable()->default('individual');
                $table->string('risk_grade', 50)->nullable();
                $table->string('loan_officer', 100)->nullable();
                $table->unsignedInteger('branch_id')->nullable()->index();
                $table->string('branch_name', 100)->nullable();
                $table->string('mobile', 30)->nullable()->index();
                $table->string('phone', 30)->nullable();
                $table->string('alternate_mobile', 30)->nullable();
                $table->string('email', 191)->nullable()->default('No Email ID');
                $table->text('address')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('city', 100)->nullable();
                $table->string('district', 100)->nullable();
                $table->string('province', 100)->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('occupation', 100)->nullable();
                $table->string('employer_name', 150)->nullable();
                $table->decimal('monthly_income', 22, 4)->default(0);
                $table->string('status', 30)->default('active')->index();
                $table->text('notes')->nullable();
                $table->string('photo')->nullable();
                $table->string('nic_front_image')->nullable();
                $table->string('nic_back_image')->nullable();
                $table->string('signature_image')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            return;
        }

        $columns = [
            'title' => ['string', 20, 'customer_no'],
            'first_name' => ['string', 100, 'title'],
            'middle_name' => ['string', 100, 'first_name'],
            'last_name' => ['string', 100, 'middle_name'],
            'date_of_birth' => ['date', null, 'nic'],
            'gender' => ['string', 30, 'date_of_birth'],
            'marital_status' => ['string', 30, 'gender'],
            'customer_type' => ['string', 50, 'marital_status'],
            'risk_grade' => ['string', 50, 'customer_type'],
            'loan_officer' => ['string', 100, 'risk_grade'],
            'branch_id' => ['integer', null, 'loan_officer'],
            'branch_name' => ['string', 100, 'branch_id'],
            'phone' => ['string', 30, 'mobile'],
            'address_line_2' => ['string', 255, 'address'],
            'district' => ['string', 100, 'city'],
            'province' => ['string', 100, 'district'],
            'postal_code' => ['string', 20, 'province'],
            'occupation' => ['string', 100, 'postal_code'],
            'employer_name' => ['string', 150, 'occupation'],
            'monthly_income' => ['decimal', null, 'employer_name'],
            'photo' => ['string', 255, 'notes'],
            'nic_front_image' => ['string', 255, 'photo'],
            'nic_back_image' => ['string', 255, 'nic_front_image'],
            'signature_image' => ['string', 255, 'nic_back_image'],
            'updated_by' => ['integer', null, 'created_by'],
        ];

        foreach ($columns as $column => $definition) {
            if (Schema::hasColumn('loan_customers', $column)) {
                continue;
            }

            Schema::table('loan_customers', function (Blueprint $table) use ($column, $definition) {
                [$type, $length, $after] = $definition;
                if ($type === 'string') {
                    $col = $table->string($column, $length)->nullable();
                } elseif ($type === 'date') {
                    $col = $table->date($column)->nullable();
                } elseif ($type === 'decimal') {
                    $col = $table->decimal($column, 22, 4)->default(0);
                } elseif ($type === 'integer') {
                    $col = $table->unsignedInteger($column)->nullable();
                } else {
                    $col = $table->string($column)->nullable();
                }

                if (!empty($after) && Schema::hasColumn('loan_customers', $after)) {
                    $col->after($after);
                }
            });
        }
    }
}
