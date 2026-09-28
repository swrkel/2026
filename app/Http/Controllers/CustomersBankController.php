<?php

namespace App\Http\Controllers;

use App\Contact;
use App\ContactGroup;
use App\CustomerGroup;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomersBankController extends ContactController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $type = 'customer';
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            // Get customers that specifically have register_module = 'bank'
            $query = $this->getCustomerContact($business_id, false)
                ->where('contacts.register_module', 'bank')
                ->leftJoin('loan_bank_customers', 'contacts.id', '=', 'loan_bank_customers.contact_id')
                ->addSelect([
                    'contacts.nic_number',
                    'loan_bank_customers.passport_number',
                    'loan_bank_customers.passport_image',
                    'loan_bank_customers.nic_image',
                ]);

            $finalSql = DB::table(DB::raw("({$query->toSql()}) as sub"))
                ->mergeBindings($query->getQuery());

            $request_length = (int)(request()->length ?? 50);
            if ($request_length <= 0) {
                $request_length = 50;
            }
            $row_ids = $finalSql->skip((int)(request()->start ?? 0))->take($request_length)->pluck('id')->toArray();

            $contacts_balances = [];
            foreach (array_chunk($row_ids, 500) as $chunk) {
                $contacts_balances += $this->contactUtil->getContactsBalance($chunk, $business_id);
            }

            return Datatables::of($finalSql)
                ->addColumn('mass_delete', function ($row) {
                    return '<input type="checkbox" class="row-select" value="' . $row->id . '">';
                })
                ->addColumn('action', function ($row) use ($contacts_balances) {
                    $row->total_sell_return = $contacts_balances[$row->id]['total_sell_return'] ?? 0;
                    $row->sell_return_paid = $contacts_balances[$row->id]['sell_return_paid'] ?? 0;
                    $row->total_due = $contacts_balances[$row->id]['total_balance'] ?? 0;

                    return view('contact.customer-actions', $this->getCustomerActionData($row))->render();
                })
                ->addColumn('return_due', function ($row) use ($contacts_balances) {
                    $total_sell_return = $contacts_balances[$row->id]['total_sell_return'] ?? 0;
                    $sell_return_paid = $contacts_balances[$row->id]['sell_return_paid'] ?? 0;

                    $row->total_sell_return = $total_sell_return;
                    $row->sell_return_paid = $sell_return_paid;

                    $html = '<span class="display_currency" data-currency_symbol="true" data-orig-value=\'' . json_encode($row) . '\'>' . $this->commonUtil->num_f($total_sell_return - $sell_return_paid) . '</span>';
                    return $html;
                })
                ->addColumn('sn', function () {
                    static $i = 0;
                    return ++$i;
                })
                ->addColumn('image', function ($row) {
                    if (isset($row->image) && $row->image != null) {
                        $image = asset('uploads/media/' . $row->image);
                        return '<img class="popup" src="' . $image . '" height="50" width="50" >';
                    } else {
                        return '';
                    }
                })
                ->addColumn('signature', function ($row) {
                    if (isset($row->signature) && $row->signature != null) {
                        $signature = asset('uploads/media/' . $row->signature);
                        return '<img class="popup" src="' . $signature . '" height="50" width="50" >';
                    } else {
                        return '';
                    }
                })
                ->addColumn('nic_image', function ($row) {
                    if (isset($row->nic_image) && $row->nic_image != null) {
                        $nic_image = asset('uploads/media/' . $row->nic_image);
                        return '<img class="popup" src="' . $nic_image . '" height="50" width="50" >';
                    } else {
                        return '';
                    }
                })
                ->addColumn('passport_image', function ($row) {
                    if (isset($row->passport_image) && $row->passport_image != null) {
                        $passport_image = asset('uploads/media/' . $row->passport_image);
                        return '<img class="popup" src="' . $passport_image . '" height="50" width="50" >';
                    } else {
                        return '';
                    }
                })
                ->editColumn('name', fn($row) => $row->name ?: '-')
                ->editColumn('mobile', fn($row) => $row->mobile ?: '-')
                ->rawColumns(['action', 'return_due', 'image', 'signature', 'nic_image', 'passport_image', 'mass_delete'])
                ->make(true);
        }

        $reward_enabled = (request()->session()->get('business.enable_rp') == 1);
        $contact_fields = session('business.contact_fields', []);
        $user_groups = User::forDropdown($business_id);
        $is_property = false;
        $contact_id = $this->businessUtil->check_customer_code($business_id);

        return view('contact.index', compact('type', 'reward_enabled', 'contact_fields', 'is_property', 'user_groups', 'contact_id'))->with('is_bank_customer', true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('customer.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('txt.both_supplier_customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);
        $supplier_groups = ContactGroup::forDropdown($business_id, true);

        // Check customer code and get contact ID
        $contact_id = $this->businessUtil->check_customer_code($business_id);

        $businessLocations = \App\BusinessLocation::whereBusinessId($business_id)->pluck('name', 'id');
        $notifications = \App\NotificationTemplate::customerNotifications();
        $customers = Contact::customersDropdown($business_id, false);
        $user_groups = User::forDropdown($business_id);
        $customerSettings = \Modules\Airline\Entities\AirlineFormSettingCustomer::where('business_id', $business_id)->first();
        $contact_fields = session('business.contact_fields', []);
        $type = 'customer';
        $mode = request()->mode;
        $module = 'other';

        return view('contact.create', compact('types', 'customer_groups', 'supplier_groups', 'contact_id', 'businessLocations', 'notifications', 'customers', 'user_groups', 'customerSettings', 'contact_fields', 'type', 'mode', 'module'))->with('is_bank_customer', true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Force type to customer and module to bank
        $request->merge([
            'type' => 'customer',
            'module' => 'bank'
        ]);

        $nic_image = null;
        if ($request->hasFile('nic_image')) {
            $nic_image = \App\Media::uploadFile($request->file('nic_image'));
        }
        $passport_image = null;
        if ($request->hasFile('passport_image')) {
            $passport_image = \App\Media::uploadFile($request->file('passport_image'));
        }
        $passport_number = $request->input('passport_number');

        $response = parent::store($request);

        $business_id = $request->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)
            ->where('register_module', 'bank')
            ->latest('id')
            ->first();

        if ($contact) {
            \App\LoanBankCustomer::updateOrCreate(
                ['contact_id' => $contact->id],
                [
                    'passport_number' => $passport_number,
                    'passport_image' => $passport_image,
                    'nic_image' => $nic_image,
                ]
            );
        }

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return redirect('/bank-customers')->with($response->getSession()->get('status'));
        }

        return $response;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('customer.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        // Load relations/properties to make them available to the blade view
        $bankDetails = $contact->bankCustomer;
        if ($bankDetails) {
            $contact->passport_number = $bankDetails->passport_number;
            $contact->passport_image = $bankDetails->passport_image;
            $contact->nic_image = $bankDetails->nic_image;
        } else {
            $contact->passport_number = null;
            $contact->passport_image = null;
            $contact->nic_image = null;
        }

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('txt.both_supplier_customer');
        }

        $customer_groups = CustomerGroup::forDropdown($business_id);
        $supplier_groups = ContactGroup::forDropdown($business_id, true);

        $ob_transaction = \App\Transaction::where('contact_id', $id)
            ->where('type', 'opening_balance')
            ->first();
        $opening_balance = !empty($ob_transaction->final_total) ? $ob_transaction->final_total : 0;
        if (!empty($opening_balance)) {
            $opening_balance_paid = $this->transactionUtil->getTotalAmountPaid($ob_transaction->id);
            if (!empty($opening_balance_paid)) {
                $opening_balance = $opening_balance - $opening_balance_paid;
            }
            $opening_balance = $this->commonUtil->num_f($ob_transaction->final_total);
        }

        $notifications = \App\NotificationTemplate::customerNotifications();
        $customers = Contact::customersDropdown($business_id, false);
        $user_groups = User::forDropdown($business_id);
        $contact_id = $this->businessUtil->check_customer_code($business_id);
        $customerSettings = \Modules\Airline\Entities\AirlineFormSettingCustomer::where('business_id', $business_id)->first();
        $contact_fields = session('business.contact_fields', []);

        return view('contact.edit', compact('contact', 'types', 'customer_groups', 'supplier_groups', 'opening_balance', 'ob_transaction', 'notifications', 'customers', 'user_groups', 'contact_id', 'customerSettings', 'contact_fields'))->with('is_bank_customer', true);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Force type to customer
        $request->merge([
            'type' => 'customer'
        ]);

        $business_id = $request->session()->get('user.business_id');
        $contact = Contact::where('business_id', $business_id)->findOrFail($id);

        // Upload new files if provided
        $bankDetails = \App\LoanBankCustomer::firstOrCreate(['contact_id' => $contact->id]);

        if ($request->hasFile('nic_image')) {
            $bankDetails->nic_image = \App\Media::uploadFile($request->file('nic_image'));
        }
        if ($request->hasFile('passport_image')) {
            $bankDetails->passport_image = \App\Media::uploadFile($request->file('passport_image'));
        }
        if ($request->has('passport_number')) {
            $bankDetails->passport_number = $request->input('passport_number');
        }
        $bankDetails->save();

        $response = parent::update($request, $id);

        // If it's a redirect, we redirect to bank-customers
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return redirect('/bank-customers')->with($response->getSession()->get('status'));
        }

        return $response;
    }

    private function getCustomerActionData($row)
    {
        return [
            'id' => $row->id,
            'total_sell_return' => $row->total_sell_return,
            'sell_return_paid' => $row->sell_return_paid,
            'should_notify' => $row->should_notify,
            'is_default' => $row->is_default,
            'type' => $row->type,
            'active' => $row->active,
            'total_due' => $row->total_due ?? 0,
        ];
    }
}
