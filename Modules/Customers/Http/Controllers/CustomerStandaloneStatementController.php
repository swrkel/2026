<?php
namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\CustomerStatement;
use App\CustomerStatementDetail;
use App\CustomerStatementLogo;
use App\CustomerStatementSetting;
use App\Exports\CustomerStatement as CustomerStatementExport;
use App\Exports\CustomerStatementPmt as CustomerStatementPmtExport;
use App\Exports\ListCustomerStatement as ListCustomerStatement;
use App\ReportConfiguration;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel as MatExcel;
use Modules\Fleet\Entities\RouteProduct;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Superadmin\Entities\HelpExplanation;
use Modules\Customers\Services\CustomerPaymentReportService;
use Modules\Customers\Support\SchemaCache;
use Modules\Superadmin\Entities\Subscription;
use App\Services\Documents\GlobalMpdf as Mpdf;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class CustomerStandaloneStatementController extends Controller
{

    /**
     * All Utils instance.
     *

     */

    protected $transactionUtil;

    protected $productUtil;

    protected $moduleUtil;

    protected $commonUtil;

    protected $businessUtil;

    protected $contactUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct(BusinessUtil $businessUtil, Util $commonUtil, TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, ContactUtil $contactUtil)
    {

        $this->transactionUtil = $transactionUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->commonUtil = $commonUtil;

        $this->businessUtil = $businessUtil;

        $this->contactUtil = $contactUtil;
    }

    private function permittedLocations(): ?array
    {
        /** @var \App\User|null $user */
        $user = Auth::user();

        if (empty($user) || !method_exists($user, 'permitted_locations')) {
            return null;
        }

        $permittedLocations = $user->permitted_locations();

        return $permittedLocations === 'all' ? null : (array) $permittedLocations;
    }

    private function userCan(string $ability): bool
    {
        /** @var \App\User|null $user */
        $user = Auth::user();

        return !empty($user) && Gate::forUser($user)->allows($ability);
    }

    private function currentBusinessId(): int
    {
        return (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
    }

    private function customerStatementReportSettings(int $businessId): array
    {
        $record = ReportConfiguration::where('business_id', $businessId)
            ->where('name', 'customer_statement_report')
            ->first();

        if (empty($record)) {
            return [];
        }

        $settings = json_decode((string) $record->configurations, true);

        return is_array($settings) ? $settings : [];
    }

    private function activePackageDetails(int $businessId): array
    {
        $subscription = Subscription::active_subscription($businessId);
        $details = optional($subscription)->package_details;

        if (is_string($details)) {
            $decoded = json_decode($details, true);
            $details = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        return is_array($details) ? $details : [];
    }

    private function statementPaymentMethodMap($statementDetails, int $businessId)
    {
        $paymentIds = collect($statementDetails)
            ->where('type', 'payment')
            ->pluck('statement_id')
            ->filter()
            ->unique()
            ->values();

        if ($paymentIds->isEmpty()) {
            return collect();
        }

        return TransactionPayment::leftJoin('accounts', function ($join) use ($businessId) {
                $join->on('transaction_payments.account_id', '=', 'accounts.id')
                    ->where('accounts.business_id', $businessId);
            })
            ->where('transaction_payments.business_id', $businessId)
            ->whereIn('transaction_payments.id', $paymentIds)
            ->select('transaction_payments.*', 'accounts.name as account_name')
            ->get()
            ->keyBy('id');
    }

    /**
     * Payment-inclusive Customer Statement support.
     *
     * Uses the Customers payment-report service so Bulk Payment allocation rows,
     * historical base/-N duplicate references and normal customer payments are
     * represented as one logical payment.  The original Customer Statement page
     * remains transaction-only; these helpers are used only by Customer
     * Statement - Pymts and the existing payment-inclusive print/export views.
     */
    private function logicalStatementPayments(int $businessId, int $contactId, string $startDate, string $endDate)
    {
        $result = app(CustomerPaymentReportService::class)->data($businessId, 5000, [
            'customer_id' => $contactId,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $rows = collect($result['rows'] ?? []);
        if ($rows->isEmpty()) {
            $rows = collect();
        }

        $paymentIds = $rows->pluck('id')->filter()->map(function ($id) {
            return (int) $id;
        })->unique()->values();

        $details = collect();
        if ($paymentIds->isNotEmpty() && SchemaCache::hasTable('transaction_payments')) {
            $detailQuery = DB::table('transaction_payments as statement_payment_tp')
                ->where('statement_payment_tp.business_id', $businessId)
                ->whereIn('statement_payment_tp.id', $paymentIds->all());

            $hasAccounts = SchemaCache::hasTable('accounts')
                && SchemaCache::hasColumn('transaction_payments', 'account_id');
            if ($hasAccounts) {
                $detailQuery->leftJoin('accounts as statement_payment_account', function ($join) use ($businessId) {
                    $join->on('statement_payment_account.id', '=', 'statement_payment_tp.account_id')
                        ->where('statement_payment_account.business_id', $businessId);
                });
            }

            $hasTransactionJoin = SchemaCache::hasTable('transactions')
                && SchemaCache::hasColumn('transaction_payments', 'transaction_id')
                && SchemaCache::hasColumn('transactions', 'id');
            if ($hasTransactionJoin) {
                $detailQuery->leftJoin('transactions as statement_payment_transaction', 'statement_payment_tp.transaction_id', '=', 'statement_payment_transaction.id');
            }

            $hasLocationJoin = $hasTransactionJoin
                && SchemaCache::hasTable('business_locations')
                && SchemaCache::hasColumn('transactions', 'location_id')
                && SchemaCache::hasColumn('business_locations', 'id');
            if ($hasLocationJoin) {
                $detailQuery->leftJoin('business_locations as statement_payment_location', 'statement_payment_transaction.location_id', '=', 'statement_payment_location.id');
            }

            $detailQuery->select('statement_payment_tp.id');
            $detailQuery->selectRaw(SchemaCache::hasColumn('transaction_payments', 'cheque_date')
                ? 'statement_payment_tp.cheque_date as cheque_date' : 'NULL as cheque_date');
            $detailQuery->selectRaw(SchemaCache::hasColumn('transaction_payments', 'bank_name')
                ? "COALESCE(statement_payment_tp.bank_name, '') as bank_name" : "'' as bank_name");
            $detailQuery->selectRaw(SchemaCache::hasColumn('transaction_payments', 'cheque_number')
                ? "COALESCE(statement_payment_tp.cheque_number, '') as cheque_number" : "'' as cheque_number");
            $detailQuery->selectRaw($hasAccounts
                ? "COALESCE(statement_payment_account.name, '') as account_name" : "'' as account_name");
            $detailQuery->selectRaw($hasLocationJoin
                ? "COALESCE(statement_payment_location.name, '') as location_name" : "'' as location_name");

            $details = $detailQuery->get()->keyBy('id');
        }

        $logicalRows = $rows->map(function ($row) use ($details) {
            $detail = $details->get((int) ($row->id ?? 0));
            $bankName = trim((string) ($row->bank_name ?? ''));
            if ($bankName === '' && $detail) {
                $bankName = trim((string) ($detail->bank_name ?? ''));
            }
            if ($bankName === '' && $detail) {
                $bankName = trim((string) ($detail->account_name ?? ''));
            }

            $chequeNumber = trim((string) ($row->cheque_number ?? ''));
            if ($chequeNumber === '' && $detail) {
                $chequeNumber = trim((string) ($detail->cheque_number ?? ''));
            }
            $chequeDate = $detail ? ($detail->cheque_date ?? null) : null;
            $method = trim((string) ($row->method ?? ''));

            return (object) [
                'id' => (int) ($row->id ?? 0),
                'date' => (string) ($row->paid_on ?? ''),
                'amount' => abs((float) ($row->amount ?? 0)),
                'customer_name' => (string) ($row->customer_name ?? ''),
                'payment_ref_no' => (string) ($row->payment_ref_no ?? ''),
                'invoice_no' => (string) ($row->invoice_no ?? ''),
                'method' => $method,
                'bank_name' => $bankName,
                'cheque_number' => $chequeNumber,
                'cheque_date' => $chequeDate,
                'account_name' => $detail ? (string) ($detail->account_name ?? '') : '',
                'location_name' => $detail ? (string) ($detail->location_name ?? '') : '',
                'source' => 'transaction_payment',
                'payment_description' => $this->statementPaymentDescription($method, $bankName, $chequeNumber, $chequeDate),
            ];
        })->filter(function ($row) {
            return $row->amount > 0;
        })->values();

        // Preserve the legacy Petro customer_payments source used by the former
        // payment-inclusive statement.  It is appended only when it is not a
        // mirror of a logical transaction_payments row.
        try {
            $legacy = $this->__getCustomerPayments($startDate, $endDate, $businessId, $contactId)->get();
            $legacyIds = $legacy->pluck('payment_row')->filter()->map(function ($id) {
                return (int) $id;
            })->unique()->values();
            $legacyModels = $legacyIds->isEmpty()
                ? collect()
                : CustomerPayment::whereIn('id', $legacyIds->all())->get()->keyBy('id');

            foreach ($legacy as $legacyRow) {
                $amount = abs((float) ($legacyRow->invoice_amount ?? 0));
                if ($amount <= 0) {
                    continue;
                }

                $legacyDate = (string) ($legacyRow->date ?? '');
                $legacyReference = trim((string) ($legacyRow->invoice_no ?? ''));

                $isMirror = $logicalRows->contains(function ($logical) use ($legacyDate, $amount, $legacyReference) {
                    if ((string) $logical->date !== $legacyDate || abs((float) $logical->amount - $amount) > 0.0001) {
                        return false;
                    }

                    if ($legacyReference === '') {
                        return false;
                    }

                    return stripos((string) $logical->invoice_no, $legacyReference) !== false
                        || stripos((string) $logical->payment_ref_no, $legacyReference) !== false;
                });

                if ($isMirror) {
                    continue;
                }

                $model = $legacyModels->get((int) ($legacyRow->payment_row ?? 0));
                $method = $model ? (string) ($model->method ?? $model->payment_method ?? '') : '';
                $bankName = $model ? trim((string) ($model->bank_name ?? '')) : trim((string) ($legacyRow->customer_reference ?? ''));
                $chequeNumber = $model ? trim((string) ($model->cheque_number ?? '')) : '';
                $chequeDate = $model ? ($model->cheque_date ?? null) : null;

                $logicalRows->push((object) [
                    'id' => (int) ($legacyRow->payment_row ?? 0),
                    'date' => $legacyDate,
                    'amount' => $amount,
                    'customer_name' => '',
                    'payment_ref_no' => $legacyReference,
                    'invoice_no' => $legacyReference,
                    'method' => $method,
                    'bank_name' => $bankName,
                    'cheque_number' => $chequeNumber,
                    'cheque_date' => $chequeDate,
                    'account_name' => '',
                    'location_name' => (string) ($legacyRow->location ?? ''),
                    'source' => 'customer_payment',
                    'payment_description' => $this->statementPaymentDescription($method, $bankName, $chequeNumber, $chequeDate),
                ]);
            }
        } catch (\Throwable $e) {
            // Some tenants do not have the Petro legacy payment tables.  The
            // Customers transaction_payments source above remains authoritative.
            Log::debug('Customer Statement - Pymts legacy payment source unavailable', [
                'business_id' => $businessId,
                'customer_id' => $contactId,
                'message' => $e->getMessage(),
            ]);
        }

        return $logicalRows->sortBy(function ($row) {
            return (string) $row->date . '|' . str_pad((string) $row->id, 12, '0', STR_PAD_LEFT);
        })->values();
    }

    private function statementPaymentDescription(string $method, string $bankName = '', string $chequeNumber = '', $chequeDate = null): string
    {
        $normalised = strtolower(trim(str_replace(['-', ' '], '_', $method)));

        if ($normalised === 'cash') {
            return 'Paid by Cash';
        }

        if (in_array($normalised, ['cheque', 'check'], true) || $chequeNumber !== '') {
            $parts = ['Paid by Cheque.'];
            if ($bankName !== '') {
                $parts[] = 'Bank: ' . $bankName;
            }
            if ($chequeNumber !== '') {
                $parts[] = 'Cheque No: ' . $chequeNumber;
            }
            if (!empty($chequeDate)) {
                try {
                    $parts[] = 'Cheque Date: ' . $this->commonUtil->format_date($chequeDate);
                } catch (\Throwable $e) {
                    $parts[] = 'Cheque Date: ' . (string) $chequeDate;
                }
            }

            return implode('    ', $parts);
        }

        if (in_array($normalised, ['bank', 'bank_transfer', 'online', 'online_transfer', 'bank_online', 'direct_deposit'], true)
            || ($normalised === '' && $bankName !== '')) {
            return 'Paid by Bank transfer / Online';
        }

        if ($normalised === 'card' || $normalised === 'credit_card' || $normalised === 'debit_card') {
            return 'Paid by Card';
        }

        if ($normalised !== '') {
            return 'Paid by ' . ucwords(str_replace('_', ' ', $normalised));
        }

        return 'Payment received';
    }

    private function statementPaymentScreenRows(int $businessId, int $contactId, string $startDate, string $endDate)
    {
        return $this->logicalStatementPayments($businessId, $contactId, $startDate, $endDate)
            ->map(function ($payment) use ($contactId) {
                return (object) [
                    'row_type' => 'payment',
                    'payment_description' => $payment->payment_description,
                    'customer_name' => $payment->customer_name,
                    'customer_id' => $contactId,
                    'location_name' => '',
                    'sku' => '',
                    'product' => '',
                    'p_unit_price' => 0,
                    'type' => '',
                    'product_id' => null,
                    'unit' => '',
                    'enable_stock' => 0,
                    'unit_price' => 0,
                    'product_variation' => '',
                    'variation_name' => '',
                    'sold_qty' => 0,
                    'transaction_date' => $payment->date,
                    'tran_type' => 'statement_payment',
                    'ref_no' => $payment->payment_ref_no,
                    'invoice_no' => '',
                    'customer_ref' => '',
                    'order_no' => '',
                    'order_date' => null,
                    'contact_id' => $contactId,
                    'sub_type' => '',
                    'order_number' => '',
                    'date_of_operation' => null,
                    'vehicle_number' => '',
                    'route_name' => '',
                    'final_total' => -abs((float) $payment->amount),
                    'transaction_id' => null,
                    'tsl_id' => 'payment-' . $payment->source . '-' . $payment->id,
                    'product_discount' => 0,
                    'total_discount' => 0,
                    'reference' => '',
                    'total_paid' => 0,
                    'qty' => 0,
                ];
            });
    }

    private function paymentInclusiveStatementDetails(CustomerStatement $statement, Contact $contact)
    {
        $businessId = (int) $statement->business_id;
        $details = CustomerStatementDetail::where('business_id', $businessId)
            ->where('statement_id', $statement->id)
            ->select(
                'id', 'business_id', 'statement_id', 'date', 'location', 'invoice_no',
                'customer_reference', 'order_no', 'order_date', 'product', 'unit_price',
                'qty', 'vehicle_number', 'route_name', 'invoice_amount', 'due_amount', 'type'
            )
            ->get();

        $paymentRows = $this->logicalStatementPayments(
            $businessId,
            (int) $contact->id,
            (string) $statement->date_from,
            (string) $statement->date_to
        )->map(function ($payment) use ($businessId) {
            return (object) [
                'id' => 'payment-' . $payment->source . '-' . $payment->id,
                'business_id' => $businessId,
                'statement_id' => $payment->source === 'transaction_payment' ? (int) $payment->id : null,
                'date' => $payment->date,
                'location' => $payment->location_name,
                'invoice_no' => $payment->payment_ref_no,
                'customer_reference' => '',
                'order_no' => '',
                'order_date' => $payment->date,
                'product' => '',
                'unit_price' => 0,
                'qty' => 0,
                'vehicle_number' => '',
                'route_name' => '',
                'invoice_amount' => abs((float) $payment->amount),
                'due_amount' => 0,
                'type' => $payment->source === 'transaction_payment' ? 'payment' : 'customer_payment',
                'payment_description' => $payment->payment_description,
            ];
        });

        return $details->concat($paymentRows)->sortBy(function ($row) {
            $isPayment = in_array((string) ($row->type ?? ''), ['payment', 'customer_payment'], true) ? '1' : '0';
            return (string) ($row->date ?? '') . '|' . $isPayment . '|' . (string) ($row->id ?? '');
        })->values();
    }

    public function indexPymts(Request $request)
    {
        $request->attributes->set('include_statement_payments', true);
        return $this->index($request);
    }

    public function getUserActivityReport(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $business_users = User::where('business_id', $business_id)->pluck('id')->toArray();

            $activityTable = (new Activity())->getTable();
            $activity = Activity::query()
                ->leftJoin('users as activity_users', 'activity_users.id', '=', $activityTable . '.causer_id')
                ->whereIn($activityTable . '.causer_id', $business_users)
                ->select($activityTable . '.*', 'activity_users.username as causer_username');

            if (! empty(request()->user) && request()->user != 'All') {

                $user = request()->user;

                $activity->where($activityTable . '.causer_id', $user);
            }

            if (! empty(request()->type) && request()->type != 'All') {
                $type = request()->type;

                $activity->where($activityTable . '.description', $type);
            }
            if (! empty(request()->subject) && request()->subject != 'All') {

                $subject = request()->subject;

                $activity->where($activityTable . '.log_name', $subject);
            }

            if (! empty(request()->startDate) && ! empty(request()->endDate)) {

                $activity->whereDate($activityTable . '.created_at', '>=', request()->startDate);

                $activity->whereDate($activityTable . '.created_at', '<=', request()->endDate);
            }

            $datatable = Datatables::of($activity)

                ->editColumn('created_at', '{{ @format_datetime($created_at) }}')

                ->removeColumn('id')

                ->editColumn('causer_id', function ($row) {
                    return $row->causer_username ?: __('lang_v1.not_applicable');
                })

                ->addColumn('description_details', function ($row) use ($business_id) {
                    $attributes = json_decode($row->properties, true);

                    $new  = $attributes['attributes'] ?? [];
                    $old  = $attributes['old'] ?? [];
                    $html = "";

                    if ($row->description == 'updated') {

                        foreach ($new as $key => $newValue) {
                            if ($key != 'created_at' && $key != 'updated_at' && $key != 'id') {
                                $oldValue = $old[$key] ?? null;
                                if ($key == 'payment_method' && $oldValue == 'Cash') {
                                    $oldValue = 'Cash ';
                                }

                                if ($newValue !== $oldValue) {
                                    $originalKey = str_replace('_', ' ', ucfirst($key));
                                    if ($originalKey !== 'Account' and $oldValue !== 'Cash') {
                                        $html .= "Original $originalKey $oldValue changed to $newValue <br>";
                                    }
                                }
                            }
                        }
                    } elseif ($row->description == 'deleted') {
                        if ($row->subject_type == 'App\TransactionPayment') {
                            static $contactNames = [];
                            $paymentFor = (int) ($new['payment_for'] ?? 0);
                            if ($paymentFor > 0 && !array_key_exists($paymentFor, $contactNames)) {
                                $contactNames[$paymentFor] = Contact::where('business_id', $business_id)
                                    ->where('id', $paymentFor)
                                    ->value('name');
                            }
                            if (!empty($contactNames[$paymentFor])) {
                                $html .= "Contact Name: " . $contactNames[$paymentFor] . "<br>";
                            }

                            if (! empty($new['amount'])) {
                                $html .= 'Amount: ' . $this->productUtil->num_f($new['amount']) . "<br>";
                            }

                            if (! empty($new['payment_ref_no'])) {
                                $html .= 'Ref No: ' . $new['payment_ref_no'] . "<br>";
                            }
                        } else {
                            return "";
                        }
                    } elseif (($row->description == 'update' || $row->description == 'delete')) {
                        // Modified by Engr. Alex -- task 7889: properties is a Spatie Collection, not a raw JSON string
                        $props = $row->properties;
                        $text  = $props->get('message', null) ?? $props->first() ?? '';
                        $html .= nl2br(e($text));

                    // Modified by Engr. Alex -- task 7889: show message for edit activities logged by CustomerPaymentController
                    } elseif ($row->description == 'edit') {
                        // properties is a Spatie Collection; use getExtraProperty() instead of json_decode
                        $message = $row->getExtraProperty('message', '');
                        $html .= nl2br(e($message));

                    } else {
                        $html = "";
                    }

                    return nl2br($html);
                });

            $rawColumns = ['description_details'];

            return $datatable->rawColumns($rawColumns)

                ->make(true);
        }

        $users = User::where('business_id', $business_id)->pluck('username', 'id');

        $type    = Activity::distinct()->pluck('description');
        $subject = Activity::distinct()->pluck('log_name');

        return view('customers::customer_statement.user_activity')

            ->with(compact('users', 'type', 'subject'));
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $includeStatementPayments = (bool) $request->attributes->get('include_statement_payments', false);
        $business_id = request()->session()->get('user.business_id');

        $default_start = new Carbon('first day of this month');

        $default_end = new Carbon('last day of this month');

        $start_date = ! empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');

        $end_date = ! empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

        // Validate date range
        if (!empty($request->get('start_date')) && !empty($request->get('end_date'))) {
            $start_carbon = Carbon::parse($start_date);
            $end_carbon = Carbon::parse($end_date);
            
            if ($end_carbon->lt($start_carbon)) {
                // Swap dates if end is before start
                $temp = $start_date;
                $start_date = $end_date;
                $end_date = $temp;
            }
        }

        $edit_customer_statement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_customer_statement');

        //Return the details in ajax call

        if ($request->ajax()) {
            // dd('masokkk');

            $query = $this->__getStatement($business_id);

            // filters:
            $permitted_locations = $this->permittedLocations();
            $location_filter     = '';
            if (!empty($permitted_locations)) {
                $query->whereIn('transactions.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (! empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('transactions.location_id', $location_id);
                $location_filter .= "AND transactions.location_id=$location_id";
                $query->join('product_locations as pls', 'pls.product_id', '=', 'p.id')
                    ->where(function ($q) use ($location_id) {
                        $q->where('pls.location_id', $location_id);
                    });
            }
            if (! empty($request->input('reference'))) {
                $reference_id = $request->input('reference');
                $customer_ref = CustomerReference::find($reference_id);
                if ($customer_ref) {
                    $query->where('transactions.customer_ref', $customer_ref->reference);
                }
            }

            // Apply date filtering when dates are provided
            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $query->where('transactions.contact_id', '!=', null);
            if (! empty($request->input('customer_id'))) {
                $cid = $request->input('customer_id');
                $query->where('transactions.contact_id', $cid);
            }

            // Apply customer type filtering
            if (! empty($request->input('customer_type')) && $request->input('customer_type') != 'all') {
                $customer_type = $request->input('customer_type');
                $query->join('contacts as contact_filter', 'transactions.contact_id', '=', 'contact_filter.id')
                      ->where('contact_filter.type', $customer_type);
            }

            // Apply search filtering
            if (! empty($request->input('search_term'))) {
                $search_term = $request->input('search_term');
                $query->where(function($q) use ($search_term) {
                    $q->where('transactions.invoice_no', 'like', '%' . $search_term . '%')
                      ->orWhere('contacts.name', 'like', '%' . $search_term . '%')
                      ->orWhere('transactions.ref_no', 'like', '%' . $search_term . '%');
                });
            }

            // $type = request()->get('type', null);
            // if (! empty($type)) {
            //     $query->where('p.type', $type);
            // }

            // old query to duplicate issue i replaced this part to
            $products = $query->orderBy('transactions.transaction_date', 'asc')
				->orderBy('transactions.id', 'asc')->get();
				
				/** Sapna 06-02-2026 - Re-enabled unique filtering to prevent duplicates **/
            $products = $products->unique(function ($item) {
                return $item->transaction_id . '|' . $item->tsl_id;
            })->values();

            // Customer Statement - Pymts adds one logical payment row per receipt.
            // The normal Customer Statement route never enters this block.
            if ($includeStatementPayments && ! empty($request->input('customer_id'))) {
                $paymentRows = $this->statementPaymentScreenRows(
                    (int) $business_id,
                    (int) $request->input('customer_id'),
                    (string) $start_date,
                    (string) $end_date
                );

                $products = $products->concat($paymentRows)->sortBy(function ($row) {
                    $isPayment = (($row->row_type ?? '') === 'payment') ? '1' : '0';
                    return (string) ($row->transaction_date ?? '') . '|' . $isPayment . '|' . (string) ($row->tsl_id ?? '');
                })->values();
            }

            // Route operation product IDs are stored as JSON. Resolve all names in
            // one query instead of querying RouteProduct for every DataTable row.
            $routeProductIds = $products
                ->where('tran_type', 'route_operation')
                ->pluck('product_id')
                ->filter()
                ->flatMap(function ($value) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) ? $decoded : [$decoded];
                })
                ->filter(function ($value) {
                    return is_numeric($value);
                })
                ->map(function ($value) {
                    return (int) $value;
                })
                ->unique()
                ->values();

            $routeProductNames = $routeProductIds->isEmpty()
                ? collect()
                : RouteProduct::whereIn('id', $routeProductIds)->pluck('name', 'id');
            
            /** Sapna 06-02-2026 **/
            
            // $products = $query->whereNotNull('transactions.invoice_no')->get();

            // $products = $query->get()->unique('invoice_no')->values();

            $datatable = DataTables::of($products)
                ->addColumn('customer', function ($row) {
                    if (($row->row_type ?? '') === 'payment') {
                        return e((string) ($row->payment_description ?? 'Payment received'));
                    }

                    return $row->customer_name;
                })
                ->editColumn('product', function ($row) use ($routeProductNames) {
                    if (($row->row_type ?? '') === 'payment') {
                        return '';
                    }

                    if ($row->tran_type === 'route_operation') {
                        if (empty($row->product_id)) {
                            return '';
                        }

                        $decoded = json_decode($row->product_id, true);
                        $productIds = is_array($decoded) ? $decoded : [$decoded];

                        return collect($productIds)
                            ->map(function ($productId) use ($routeProductNames) {
                                return $routeProductNames->get((int) $productId);
                            })
                            ->filter()
                            ->implode(' + ');
                    }

                    $name = $row->product;
                    if ($row->type === 'variable') {
                        $name .= ' - ' . $row->product_variation . '-' . $row->variation_name;
                    }

                    return $name;
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->addColumn('quantity', function ($row) {

                    if ($row->tran_type == 'sell' || $row->tran_type == 'route_operation') {

                        $amounts = "";
                        if (! empty($row->sold_qty)) {
                            $qty_array = json_decode($row->sold_qty);
                            if (is_array($qty_array)) {

                                foreach ($qty_array as $key => $one) {
                                    $amounts .= $this->productUtil->num_f((float) $one);
                                    if ($key != sizeof($qty_array) - 1) {
                                        $amounts .= " + ";
                                    }
                                }
                            } else {
                                $amounts .= $this->productUtil->num_f((float) $qty_array);
                            }
                        }

                        /*
                         | IS2011: wrap the quantity so the footer can total it.
                         |
                         | The displayed text can be a composite such as "25.00 + 10.00"
                         | for a bill covering several lines, which cannot be summed by
                         | reading the cell text. data-orig-value carries the single
                         | numeric total for the row, and the qty_total class is what
                         | sum_table_col() looks for - the same mechanism the invoice
                         | and due totals already use.
                         */
                        $qty_numeric = 0;

                        if (! empty($row->sold_qty)) {
                            $qty_array = json_decode($row->sold_qty);

                            if (is_array($qty_array)) {
                                foreach ($qty_array as $one) {
                                    $qty_numeric += (float) $one;
                                }
                            } else {
                                $qty_numeric = (float) $qty_array;
                            }
                        }

                        return '<span class="qty_total" data-orig-value="' . $qty_numeric . '">'
                            . $amounts . '</span>';

                    } else {

                        return '<span class="qty_total" data-orig-value="0"></span>';
                    }
                })
                ->addColumn('action', function ($row) use ($edit_customer_statement) {

                    if (($row->row_type ?? '') === 'payment') {
                        return '';
                    }

                    $html = '<div class="btn-group">

                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"

                                data-toggle="dropdown" aria-expanded="false">' .

                    /*
                     | IS2011: "Click" rather than "Actions".
                     |
                     | The button is the widest thing in the Action column, and at
                     | "Actions" it pushed into the Date column beside it. The shorter
                     | label lets the column shrink so the two no longer overlap.
                     */
                    __("Click") .

                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown

                                </span>

                            </button>

                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->transaction_id]) . '" class="btn-modal" data-container=".customer_statement_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';

                    if ($edit_customer_statement) {

                        if ($this->userCan('edit_customer_statement')) {

                            $html .= '<li><a href="#" data-href="' . url('/customers/customer-statement/' . $row->transaction_id . '/edit') . '" class="btn-modal" data-container=".customer_statement_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>';
                        }
                    }

                    return $html;
                })
                ->editColumn(

                    'transaction_date',

                    '{{@format_date($transaction_date)}}'

                )
                ->editColumn(

                    'order_no',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        if (! empty($row->order_no)) {
                            return $row->order_no;
                        } else {
                            return $row->order_number;
                        }
                    }
                )
                ->editColumn(

                    'invoice_no',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        $html = $row->invoice_no;
                        if ($row->sub_type == 'customer_loan') {
                            $html .= "<br><b>(" . __('petro::lang.customer_loans') . ")</b>";
                        }

                        if ($row->tran_type == 'direct_customer_loan') {
                            $html .= "<br><b>(" . __('lang_v1.direct_loan_to_customer') . ")</b>";
                        }

                        return $html;
                    }
                )
                ->editColumn(

                    'route_name',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        if (! empty($row->route_name)) {
                            return $row->route_name;
                        }
                    }

                )
                ->editColumn(

                    'vehicle_number',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        if (! empty($row->vehicle_number)) {
                            return $row->vehicle_number;
                        }
                    }

                )
                ->editColumn(

                    'order_date',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        if (! empty($row->order_date)) {
                            return $this->commonUtil->format_date($row->order_date);
                        }
                    }

                )

                ->addColumn(

                    'due_amount',
                    function ($row) {

                        if (($row->row_type ?? '') === 'payment') {
                            return '<span class="due" data-orig-value="0"></span>';
                        }

                        $due = 0;

                        if ($row->tran_type == 'sell') {

                            $due = ($row->sold_qty * $row->p_unit_price) - $row->total_paid;
                        } else {

                            $due = $row->final_total - $row->total_paid;
                        }

                        if (! empty($row->total_discount)) {
                            $due = $due - $row->total_discount;
                        }

                        return '<span class="display_currency due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $this->productUtil->num_f($due) . '</span>';
                    }
                )

                ->editColumn(

                    'final_total',
                    function ($row) {

                        if (($row->row_type ?? '') === 'payment') {
                            $amount = (float) ($row->final_total ?? 0);
                            return '<span class="display_currency total statement-payment-amount" data-currency_symbol="true" data-orig-value="' . $amount . '">' . $this->productUtil->num_f($amount) . '</span>';
                        }

                        if ($row->tran_type == 'sell') {

                            $due = ($row->sold_qty * $row->p_unit_price);
                        } else {

                            $due = ($row->final_total);
                        }
                        if (! empty($row->total_discount)) {
                            $due = $due - $row->total_discount;
                        }

                        return '<span class="display_currency total" data-currency_symbol="true" data-orig-value="' . $due . '">' . $this->productUtil->num_f($due) . '</span>';
                    }
                )
                ->editColumn(

                    'unit_price',

                    function ($row) {
                        if (($row->row_type ?? '') === 'payment') {
                            return '';
                        }

                        if (! empty($row->p_unit_price)) {
                            if (! empty($row->product_discount)) {
                                return $this->commonUtil->num_f($row->p_unit_price - $row->product_discount);
                            } else {
                                return $this->commonUtil->num_f($row->p_unit_price);
                            }
                        }
                    }

                )
                ->editColumn(

                    'qty',

                    function ($row) {
                        if (! empty($row->qty)) {
                            return $this->commonUtil->num_uf($row->qty);
                        }
                    }

                )
                ->editColumn(

                    'final_due_amount',

                    '{{$final_total - $total_paid}}'

                );

            $raw_columns = [

                'action',

                'final_total',

                'due_amount',

                'invoice_no',

                // IS2011: quantity now returns a span carrying data-orig-value.
                'quantity',

            ];

            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->pluck('name', 'id');

        // Customer types for filtering
        $customer_types = [
            'customer' => __('lang_v1.customer'),
            'both' => __('lang_v1.both_supplier_customer'),
            'all' => __('lang_v1.all')
        ];

        $reference = CustomerReference::where(['business_id' => $business_id])->pluck('reference', 'id');

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $enable_separate_customer_statement_no = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_separate_customer_statement_no');

        $help_explanations = HelpExplanation::pluck('value', 'help_key');

        $statement_no = CustomerStatement::where('business_id', $business_id)->count();

        $logos = CustomerStatementLogo::where('business_id', $business_id)->select('*')->pluck('image_name', 'id');

        $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;
        $payment_methods      = $this->transactionUtil->payment_types($business_location_id);

        $customer_statement = ReportConfiguration::where('business_id', $business_id)
            ->where('name', 'customer_statement_report')
            ->first();

        $customer_statement_report = !empty($customer_statement)
            ? json_decode($customer_statement->configurations, true)
            : [];


        $statement_nos = TransactionPayment::join('customer_statements', 'customer_statements.id', 'transaction_payments.linked_customer_statement')
            ->whereNull('transaction_id')
            ->whereNotNull('linked_customer_statement')
            ->where('transaction_payments.business_id', $business_id)->pluck('customer_statements.statement_no', 'customer_statements.id');

        $statementView = $includeStatementPayments
            ? 'customers::customer_statement.index-pymts'
            : 'customers::customer_statement.index';

        return view($statementView)->with(compact('statement_nos', 'payment_methods', 'logos', 'customers', 'customer_types', 'enable_separate_customer_statement_no', 'business_locations', 'statement_no', 'help_explanations','reference', 'customer_statement_report'));
    }

    public function listStatementPayments(Request $request)
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

        $default_start = new \Carbon('first day of this month');

        $default_end = new \Carbon('last day of this month');

        $start_date = ! empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');

        $end_date = ! empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

        if ($request->ajax()) {

            $statementTotals = CustomerStatementDetail::query()
                ->select('statement_id')
                ->selectRaw('SUM(invoice_amount) as statement_amount')
                ->groupBy('statement_id');

            $query = TransactionPayment::leftJoin('users', 'users.id', '=', 'transaction_payments.created_by')
                ->leftJoin('customer_statements', function ($join) use ($business_id) {
                    $join->on('customer_statements.id', '=', 'transaction_payments.linked_customer_statement')
                        ->where('customer_statements.business_id', $business_id);
                })
                ->leftJoin('contacts', function ($join) use ($business_id) {
                    $join->on('contacts.id', '=', 'transaction_payments.payment_for')
                        ->where('contacts.business_id', $business_id);
                })
                ->leftJoin('accounts as payment_account', function ($join) use ($business_id) {
                    $join->on('payment_account.id', '=', 'transaction_payments.account_id')
                        ->where('payment_account.business_id', $business_id);
                })
                ->leftJoinSub($statementTotals, 'statement_totals', function ($join) {
                    $join->on('statement_totals.statement_id', '=', 'transaction_payments.linked_customer_statement');
                })
                ->where('transaction_payments.business_id', $business_id)
                ->whereNull('transaction_payments.transaction_id')
                ->whereNotNull('transaction_payments.linked_customer_statement')
                ->whereDate('transaction_payments.paid_on', '>=', $start_date)
                ->whereDate('transaction_payments.paid_on', '<=', $end_date)
                ->select(
                    'contacts.name as customer_name',
                    'customer_statements.statement_no',
                    'users.username',
                    'payment_account.name as bank_account_name',
                    'statement_totals.statement_amount',
                    'transaction_payments.*'
                );

            if (! empty($request->input('customer_id'))) {
                $cid = $request->input('customer_id');
                $query->where('transaction_payments.payment_for', $cid);
            }

            if (! empty($request->input('statement_no'))) {
                $query->where('customer_statements.id', $request->input('statement_no'));
            }

            if (! empty($request->input('payment_method'))) {
                $query->where('transaction_payments.method', $request->input('payment_method'));
            }

            $datatable = DataTables::of($query)
                ->removeColumn('id')
                ->editColumn(
                    'paid_on',
                    '{{@format_datetime($paid_on)}}'
                )
                ->editColumn(
                    'created_at',
                    '{{@format_datetime($created_at)}}'
                )
                ->editColumn('statement_amount', function ($row) {
                    return $this->transactionUtil->num_f((float) ($row->statement_amount ?? 0));
                })

                ->editColumn('method', function ($row) {
                    $payment_method_html = ucfirst(str_replace('_', ' ', $row->method));

                    if (in_array(strtolower($row->method), ['bank_transfer', 'direct_bank_deposit', 'bank', 'cheque'], true)) {
                        if (!empty($row->bank_account_name)) {
                            $payment_method_html .= '<br><b>Bank Name:</b> ' . e($row->bank_account_name) . '</br>';
                        }

                        if (!empty($row->cheque_number)) {
                            $payment_method_html .= '<b>Cheque Number:</b> ' . e($row->cheque_number) . '</br>';
                        }
                    }

                    return $payment_method_html;
                })
                ->editColumn(

                    'amount',

                    '{{@num_format($amount)}}'

                );

            $raw_columns = [
                'method',
            ];

            return $datatable->rawColumns($raw_columns)->make(true);
        }
    }

    public function __getStatement($business_id)
    {
        $query = DB::table('transactions')
            ->leftJoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
            ->leftJoin('settlement_credit_sale_payments', 'transactions.id', '=', 'settlement_credit_sale_payments.transaction_id')
            ->leftJoin('route_operations', 'transactions.id', '=', 'route_operations.transaction_id')
            ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
//             ->leftJoin('settlements as cs_settlements', function ($join) {
//                 $join->on('transactions.invoice_no', '=', 'cs_settlements.settlement_no')
//                     ->orOn('transactions.ref_no', '=', 'cs_settlements.settlement_no');
//             })
            ->leftJoin('products as p', function ($join) {
                $join->on('p.id', '=', DB::raw('IF(transactions.is_settlement = 1, settlement_credit_sale_payments.product_id, tsl.product_id)'));
            })
            ->leftJoin('routes as r', 'route_operations.route_id', '=', 'r.id')
            ->leftJoin('fleets as f', 'route_operations.fleet_id', '=', 'f.id')
            ->leftJoin('route_products as rp', 'route_operations.product_id', '=', 'rp.id')
            ->leftJoin('variations', 'p.id', '=', 'variations.product_id')
            ->leftJoin('units', 'p.unit_id', '=', 'units.id')
            ->leftJoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('contacts.manual_bill_settlement')
                        ->orWhere('contacts.manual_bill_settlement', 0);
                })
                    ->orWhere(function ($q) {
                        $q->where('contacts.manual_bill_settlement', 1)
                            ->where('transactions.invoice_no', 'LIKE', 'INV%');
                    });
            })
            ->leftJoin('customer_references as cRef', function ($join) {
                $join->on(
                    DB::raw("CONVERT(cRef.reference USING utf8mb4) COLLATE utf8mb4_unicode_ci"),
                    '=',
                    'transactions.customer_ref'
                );
            })
            ->leftJoin('customer_statement_details as csd', function ($join) use ($business_id) {
                $join->on('transactions.id', '=', 'csd.transaction_id')
                    ->where('csd.business_id', $business_id);
            })
            /** Sapna 06-02-2026 - Re-enabled to prevent duplicates **/
            ->whereNull('csd.transaction_id')
            /** Sapna 06-02-2026 **/
            ->selectRaw('DISTINCT
            business_locations.name as location_name,
            variations.sub_sku as sku,
            COALESCE(p.name, rp.name) as product,
            COALESCE(tsl.unit_price_inc_tax, settlement_credit_sale_payments.price, variations.sell_price_inc_tax) as p_unit_price,
            p.type,
            transactions.contact_id as customer_id,
            contacts.name as customer_name,
            p.id as product_id,
            units.short_name as unit,
            p.enable_stock as enable_stock,
            variations.sell_price_inc_tax as unit_price,
            pv.name as product_variation,
            variations.name as variation_name,
            COALESCE(tsl.quantity, route_operations.qty, settlement_credit_sale_payments.qty) as sold_qty,
            route_operations.product_id,
            transactions.transaction_date as transaction_date,
            transactions.type as tran_type,
            transactions.ref_no,
            transactions.invoice_no,
            transactions.customer_ref,
            transactions.order_no,
            transactions.order_date,
            transactions.contact_id,
            transactions.sub_type,
            route_operations.order_number,
            route_operations.date_of_operation,
            COALESCE(
                f.vehicle_number,
                settlement_credit_sale_payments.customer_reference,
                transactions.customer_ref
            ) as vehicle_number,
            r.route_name,
            transactions.final_total,
            transactions.id as transaction_id,
            tsl.id as tsl_id,
            settlement_credit_sale_payments.discount as product_discount,
            settlement_credit_sale_payments.total_discount as total_discount,
            cRef.reference')
            ->leftJoin(DB::raw('(
            SELECT transaction_id, SUM(IF(is_return = 1, -amount, amount)) AS total_paid
            FROM transaction_payments
            GROUP BY transaction_id
        ) AS tp_total'), 'transactions.id', '=', 'tp_total.transaction_id')
            ->addSelect('tp_total.total_paid')
			->where(function ($query) use ($business_id) {
                $query->where('p.business_id', $business_id)
                    ->orWhere('route_operations.business_id', $business_id)
                    ->orWhere('transactions.business_id', $business_id);
            })->where(function ($query) {
                $query->whereIn('transactions.type', [
                    'direct_customer_loan', 'fleet_opening_balance', 'cheque_return',
                    'property_sell', 'route_operation', 'expense', 'sell',
                    'opening_balance', 'sell_return',
                ])
                    ->orWhere(function ($query) {
                        $query->where('transactions.type', 'settlement')
                            ->where('transactions.sub_type', 'customer_loan');
                    });
            })
            ->whereIn('transactions.payment_status', ['due', 'partial']);

        return $query;
    }

    //     public function __getStatement($business_id)
//     {
//         $query = DB::table('transactions')
//             ->leftJoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
//             ->leftJoin('settlement_credit_sale_payments', 'transactions.credit_sale_id', '=', 'settlement_credit_sale_payments.id')
//             ->leftJoin('route_operations', 'transactions.id', '=', 'route_operations.transaction_id')
//             ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
//             ->leftJoin('settlements as cs_settlements', function ($join) {
//                 $join->on('transactions.invoice_no', '=', 'cs_settlements.settlement_no')
//                     ->orOn('transactions.ref_no', '=', 'cs_settlements.settlement_no');
//             })
//             ->leftJoin('products as p', function ($join) {
//                 $join->on(function ($query) {
//                     $query->where('transactions.is_settlement', 1)
//                         ->whereColumn('p.id', 'settlement_credit_sale_payments.product_id');
//                 })
//                     ->orWhere(function ($query) {
//                         $query->where('transactions.is_settlement', '<>', 1)
//                             ->whereColumn('p.id', 'tsl.product_id');
//                     });
//             })
//             ->leftJoin('routes as r', 'route_operations.route_id', '=', 'r.id')
//             ->leftJoin('fleets as f', 'route_operations.fleet_id', '=', 'f.id')
//             ->leftJoin('route_products as rp', 'route_operations.product_id', '=', 'rp.id')
//             ->leftJoin('variations', 'p.id', '=', 'variations.product_id')
//             ->leftJoin('units', 'p.unit_id', '=', 'units.id')
//             ->leftJoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
//             ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
//             ->where(function ($query) {
//                  $query->where(function ($q) {
//                     $q->whereNull('contacts.manual_bill_settlement')
//                     ->orWhere('contacts.manual_bill_settlement', 0);
//                 })
//                 ->orWhere(function ($q) {
//                     $q->where('contacts.manual_bill_settlement', 1)
//                     ->where('transactions.invoice_no', 'LIKE', 'INV%');
//                 });
//             })
//             ->join('customer_references as cRef', function ($join) {
//                 $join->on(
//                     DB::raw("CONVERT(cRef.reference USING utf8mb4) COLLATE utf8mb4_unicode_ci"),
//                     '=',
//                     'transactions.customer_ref'
//                 );
//             })
//             ->selectRaw('
//                 business_locations.name as location_name,
//                 variations.sub_sku as sku,
//                 COALESCE(p.name, rp.name) as product,
//                 COALESCE(tsl.unit_price, settlement_credit_sale_payments.price) as p_unit_price,
//                 p.type,
//                 transactions.contact_id as customer_id,
//                 p.id as product_id,
//                 units.short_name as unit,
//                 p.enable_stock as enable_stock,
//                 variations.sell_price_inc_tax as unit_price,
//                 pv.name as product_variation,
//                 variations.name as variation_name,
//                 COALESCE(tsl.quantity, route_operations.qty, settlement_credit_sale_payments.qty) as sold_qty,
//                 route_operations.product_id,
//                 transactions.transaction_date as transaction_date,
//                 transactions.type as tran_type,
//                 transactions.ref_no,
//                 transactions.invoice_no,
//                 transactions.customer_ref,
//                 transactions.order_no,
//                 transactions.order_date,
//                 transactions.contact_id,
//                 transactions.sub_type,
//                 route_operations.order_number,
//                 route_operations.date_of_operation,
//                 f.vehicle_number,
//                 r.route_name,
//                 transactions.final_total,
//                 transactions.id as transaction_id,
//                 tsl.id as tsl_id,
//                 settlement_credit_sale_payments.discount as product_discount,
//                 settlement_credit_sale_payments.total_discount as total_discount,
//                 cRef.reference')

    //             ->leftJoin(DB::raw('(
//     SELECT transaction_id, SUM(IF(is_return = 1, -amount, amount)) AS total_paid
//     FROM transaction_payments
//     GROUP BY transaction_id
// ) AS tp_total'), 'transactions.id', '=', 'tp_total.transaction_id')
//             ->addSelect('tp_total.total_paid')

    //             ->where(function ($query) use ($business_id) {
//                 $query->where('p.business_id', $business_id)
//                     ->orWhere('route_operations.business_id', $business_id)
//                     ->orWhere('transactions.business_id', $business_id);
//             })
//             ->where(function ($query) {
//                 $query->whereIn('transactions.type', ['direct_customer_loan', 'fleet_opening_balance', 'cheque_return', 'property_sell', 'route_operation', 'expense', 'sell', 'opening_balance', 'sell_return'])
//                     ->orWhere(function ($query) {
//                         $query->where('transactions.type', 'settlement')->where('transactions.sub_type', 'customer_loan');
//                     });
//             })

//             ->whereIn('transactions.payment_status', ['due', 'partial']);

    //         return $query;
//     }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create()
    {

        //

    }

    public function downloadPdf(Request $request)
    {
        $html = $request->get('html');
        $mpdf = new Mpdf();
        $mpdf->SetFont('Calibri', '', 12);
        $mpdf->WriteHTML($html);

        $directoryPath = config('constants.reports_directory');
        if (! is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $filename = Str::random(40) . ".pdf";
        $filePath = config('constants.reports_directory') . $filename;
        $mpdf->Output($filePath, 'F');

        return response()->json(['path' => url("reports/" . $filename)]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    public function store(Request $request)
    {

        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

        try {

            $default_start = new \Carbon('first day of this month');

            $default_end = new \Carbon('last day of this month');

            $start_date = ! empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');

            $end_date = ! empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

            $query = $this->__getStatement($business_id);

            // filters:
            $permitted_locations = $this->permittedLocations();
            $location_filter     = '';
            if (!empty($permitted_locations)) {
                $query->whereIn('transactions.location_id', $permitted_locations);
                $locations_imploded = implode(', ', $permitted_locations);
                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }

            if (! empty($request->input('location_id'))) {
                $location_id = $request->input('location_id');
                $query->where('transactions.location_id', $location_id);
                $location_filter .= "AND transactions.location_id=$location_id";
                $query->join('product_locations as pls', 'pls.product_id', '=', 'p.id')
                    ->where(function ($q) use ($location_id) {
                        $q->where('pls.location_id', $location_id);
                    });
            }

            // Apply date filtering when dates are provided
            if (! empty($start_date) && ! empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $query->where('transactions.contact_id', '!=', null);
            if (! empty($request->input('customer_id'))) {
                $cid = $request->input('customer_id');
                $query->where('transactions.contact_id', $cid);
            }

            $type = request()->get('type', null);
            if (! empty($type)) {
                $query->where('p.type', $type);
            }

            $transactions = $query->get();

            if ($transactions->isEmpty()) {
                return [
                    'success' => 0,
                    'msg' => 'All bills/invoices for the selected date range have already been saved or no bills are available.',
                ];
            }

            $existingStatement = CustomerStatement::where('customer_id', $request->customer_id)
                ->where('business_id', $business_id)
                ->where(function ($q) use ($start_date, $end_date) {
                    $q->whereDate('date_to', '>=', $start_date)
                      ->whereDate('date_from', '<=', $end_date);
                })
                ->first();

            if ($existingStatement) {
                return [
                    'success' => 0,
                    'msg' => 'A customer statement already exists for this customer with an overlapping date range. Statement No: ' . $existingStatement->statement_no . ' (Date Range: ' . Carbon::parse($existingStatement->date_from)->format('Y-m-d') . ' to ' . Carbon::parse($existingStatement->date_to)->format('Y-m-d') . ')',
                ];
            }

            DB::beginTransaction();

            $contact = Contact::findOrFail($request->customer_id);

            $custAbbreviation = strtoupper(substr($contact->name, 0, 2));

            $customer_statement = CustomerStatement::where('customer_id', $request->customer_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($customer_statement && sizeof(explode('-', $customer_statement->statement_no)) > 1) {
                $previousStaement = explode('-', $customer_statement->statement_no)[1];
            } else {
                $previousStaement = 0;
            }

            $currentStatement = $custAbbreviation . "-" . ($previousStaement + 1);

            $statement = CustomerStatement::create([

                'business_id'           => $business_id,

                'logo'                  => $request->logo,

                'customer_id'           => $request->customer_id,

                'statement_no'          => $currentStatement,

                'print_date'            => date('Y-m-d'),

                'date_from'             => Carbon::parse($start_date)->format('Y-m-d'),

                'date_to'               => Carbon::parse($end_date)->format('Y-m-d'),

                'added_by'              => Auth::user()->id,

                'is_transaction_linked' => 1,

            ]);

            foreach ($transactions as $transaction) {
                $invoice_no = $transaction->invoice_no;
                if ($transaction->sub_type == 'customer_loan') {
                    $invoice_no = __('petro::lang.customer_loans');
                }

                if ($transaction->tran_type == 'direct_customer_loan') {
                    $invoice_no = __('lang_v1.direct_loan_to_customer');
                }

                $due = 0;

                if ($transaction->tran_type == 'sell') {
                    $due = ($transaction->sold_qty * $transaction->p_unit_price) - $transaction->total_paid;
                } else {
                    $due = $transaction->final_total - $transaction->total_paid;
                }

                $final_total = 0;
                if ($transaction->tran_type == 'sell') {
                    $final_total = $transaction->sold_qty * $transaction->p_unit_price;
                } else {
                    $final_total = $transaction->final_total;
                }

                CustomerStatementDetail::create([

                    'business_id'        => $business_id,

                    'statement_id'       => $statement->id,

                    'date'               => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),

                    'location'           => $transaction->location_name,

                    'invoice_no'         => $invoice_no,

                    'customer_reference' => $transaction->customer_ref,

                    'order_no'           => ! empty($transaction->order_no) ? $transaction->order_no : $transaction->order_number, // ceck this
                    'vehicle_number'     => ! empty($transaction->vehicle_number) ? $transaction->vehicle_number : "",
                    'route_name'         => ! empty($transaction->route_name) ? $transaction->route_name : "",

                    'order_date'         => ! empty($transaction->order_date) ? $transaction->order_date : $transaction->date_of_operation,

                    'product'            => $transaction->product,

                    'unit_price'         => ! empty($transaction->p_unit_price) ? $transaction->p_unit_price : "1",

                    'qty'                => $transaction->sold_qty,

                    'invoice_amount'     => $final_total,

                    'due_amount'         => $due,

                    'transaction_id'     => $transaction->transaction_id,

                ]);
            }

            $default_location = BusinessLocation::where('business_id', $business_id)->first();

            $statement->location_id = ! empty($transaction->location_id) ? $transaction->location_id : $default_location->id;

            DB::commit();

            $output = [

                'success' => 1,

                'msg'     => __('messages.success'),

            ];
        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => 0,

                'msg'     => __('messages.something_went_wrong'),

            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function show($id)
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);
        $start_date = $statement->date_from;

        $statement_details = CustomerStatementDetail::where('business_id', $business_id)
            ->where('statement_id', $id)
            ->get();

        $contact_id = $contact->id;
        $ledger_details = ['opening_balance' => 0];

        if ($contact->type === 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details'] = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        } elseif ($contact->type === 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details'] = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $for_pdf = 1;
        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);

        $transactionIds = $statement_details->pluck('transaction_id')->filter()->map(function ($value) {
            return (int) $value;
        })->unique()->values();

        $validTransactionIds = $transactionIds->isEmpty()
            ? collect()
            : Transaction::where('business_id', $business_id)
                ->where('contact_id', $contact_id)
                ->whereIn('id', $transactionIds)
                ->pluck('id')
                ->map(function ($value) {
                    return (int) $value;
                });

        $paymentTotals = $validTransactionIds->isEmpty()
            ? collect()
            : TransactionPayment::where('business_id', $business_id)
                ->whereIn('transaction_id', $validTransactionIds)
                ->select('transaction_id')
                ->selectRaw('SUM(CASE WHEN COALESCE(is_return, 0) = 1 THEN -amount ELSE amount END) as net_paid')
                ->groupBy('transaction_id')
                ->get()
                ->pluck('net_paid', 'transaction_id');

        $validTransactionLookup = $validTransactionIds->flip();
        $total_invoice_amount = 0.0;
        $total_balance_due = 0.0;

        foreach ($statement_details as $detail) {
            $invoiceAmount = (float) $detail->invoice_amount;
            $total_invoice_amount += $invoiceAmount;

            $transactionId = (int) $detail->transaction_id;
            $netPaid = $validTransactionLookup->has($transactionId)
                ? (float) $paymentTotals->get($transactionId, 0)
                : 0.0;

            $detail->balance_due = max(0, $invoiceAmount - $netPaid);
            $total_balance_due += $detail->balance_due;
        }

        $customer_statement_record = ReportConfiguration::where('business_id', $business_id)
            ->where('name', 'customer_statement_report')
            ->first();
        $customer_statement_report = !empty($customer_statement_record)
            ? (array) json_decode($customer_statement_record->configurations, true)
            : [];
        $paid_customer_statement = TransactionPayment::where('business_id', $business_id)
            ->where('linked_customer_statement', $id)
            ->count();
        $pacakge_details = $this->activePackageDetails($business_id);

        return view('customers::customer_statement.show')->with(compact(
            'logo',
            'contact',
            'ledger_details',
            'business_details',
            'for_pdf',
            'location_details',
            'statement_details',
            'statement',
            'id',
            'total_invoice_amount',
            'total_balance_due',
            'customer_statement_report',
            'paid_customer_statement',
            'pacakge_details'
        ));
    }

    public function payTotalStatement($id)
    {
        $business_id = (int) (request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id')
            ?: optional(auth()->user())->business_id);

        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $statement_details = CustomerStatementDetail::where('business_id', $business_id)
            ->where('statement_id', $id)
            ->get();
        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $payment = TransactionPayment::leftJoin('users', 'users.id', '=', 'transaction_payments.created_by')
            ->leftJoin('accounts as payment_account', function ($join) use ($business_id) {
                $join->on('payment_account.id', '=', 'transaction_payments.account_id')
                    ->where('payment_account.business_id', $business_id);
            })
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.transaction_id')
            ->where('transaction_payments.linked_customer_statement', $id)
            ->select('users.username', 'payment_account.name as bank_account_name', 'transaction_payments.*')
            ->first();

        $transaction_ids = $statement_details->pluck('transaction_id')->filter()->unique()->values();
        $paid_transactions = $transaction_ids->isEmpty()
            ? []
            : Transaction::where('business_id', $business_id)
                ->where('contact_id', $contact->id)
                ->whereIn('id', $transaction_ids)
                ->where('payment_status', 'paid')
                ->pluck('invoice_no')
                ->toArray();

        $statement_amount = $transaction_ids->isEmpty()
            ? 0.0
            : (float) Transaction::where('business_id', $business_id)
                ->where('contact_id', $contact->id)
                ->whereIn('id', $transaction_ids)
                ->sum('final_total');
        $statementPaymentTotal = $transaction_ids->isEmpty()
            ? null
            : TransactionPayment::where('business_id', $business_id)
                ->whereIn('transaction_id', $transaction_ids)
                ->selectRaw('SUM(CASE WHEN COALESCE(is_return, 0) = 1 THEN -amount ELSE amount END) as net_paid')
                ->first();
        $statement_paid_amount = (float) optional($statementPaymentTotal)->net_paid;
        $statement_due_amount = max(0, $statement_amount - $statement_paid_amount);

        $prefix_type = 'sell_payment';
        $business_location_id = (int) BusinessLocation::where('business_id', $business_id)->value('id');
        abort_if($business_location_id <= 0, 422, __('business.business_locations'));

        $payment_types = $this->transactionUtil->payment_types($business_location_id);
        unset($payment_types['credit_sale']);

        $payment_line = new TransactionPayment();
        $payment_line->amount = $statement_due_amount;
        $payment_line->method = 'cash';
        $payment_line->paid_on = Carbon::now()->toDateTimeString();

        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
        $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);
        $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
        $pacakge_details = $this->activePackageDetails($business_id);

        return view('customers::customer_statement.pay_total_statement')->with(compact(
            'payment',
            'contact',
            'paid_transactions',
            'statement_due_amount',
            'payment_types',
            'payment_line',
            'accounts',
            'payment_ref_no',
            'statement',
            'business_location_id',
            'pacakge_details'
        ));
    }

   public function postPayTotalStatement(Request $request, $id)
{
    $business_id = (int) ($request->session()->get('user.business_id')
        ?: $request->session()->get('business.id')
        ?: optional($request->user())->business_id);

    try {
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $statement_details = CustomerStatementDetail::where('business_id', $business_id)
            ->where('statement_id', $id)
            ->get();
        $transaction_ids   = $statement_details->pluck('transaction_id')->toArray();

        $contact_id = $statement->customer_id;

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

        if (! empty($has_reviewed)) {
            $output = [
                'success' => 0,
                'msg' => __('lang_v1.review_first'),
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

        if (! empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg' => "You can't add a payment for an already reviewed date",
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        $inputs = $request->only([
            'amount',
            'method',
            'note',
            'card_number',
            'card_holder_name',
            'card_transaction_number',
            'card_type',
            'card_month',
            'card_year',
            'card_security',
            'cheque_number',
            'bank_account_number',
            'bank_name',
            'post_dated_cheque',
            'update_post_dated_cheque',
        ]);

        /**
         * ====== الإضافات الآمنة (لا تكسر المنطق) ======
         * 1) السماح فقط بطرق الدفع المتوقعة (لا Credit ولا 3).
         * 2) تعطيل PD Cheque إذا لم تكن الطريقة Cheque.
         */
        $inputs['method'] = strtolower((string)($inputs['method'] ?? ''));
        $allowed_methods  = ['cash', 'card', 'cheque', 'direct_bank_deposit', 'bank_transfer'];

        if (! in_array($inputs['method'], $allowed_methods, true)) {
            $output = [
                'success' => false,
                'msg'     => __('Invalid payment method for Pay Total Statement'),
            ];
            return redirect()->back()->with('status', $output)->withInput();
        }

        // لا نسمح بخيارات PD Cheque إلا مع cheque
        if ($inputs['method'] !== 'cheque') {
            $inputs['post_dated_cheque']        = 0;
            $inputs['update_post_dated_cheque'] = 0;
        }
        /** ====== نهاية الإضافات ====== */

        if ($inputs['method'] == 'cheque') {
            if (empty($inputs['cheque_number']) || empty($inputs['bank_name'])) {
                $output = [
                    'success' => false,
                    'msg'     => 'Bank name and Cheque number are required for Cheque payments',
                ];
                return redirect()->back()->with('status', $output);
            } else {
                // check duplicates
                $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                if ($chequesAdded > 0) {
                    $output = [
                        'success' => false,
                        'msg'     => 'Cheque with the same number and bank name already exists!',
                    ];
                    return redirect()->back()->with('status', $output);
                }
            }
        }

        $inputs['paid_on'] = $this->transactionUtil->uf_date($request->input('paid_on'), true);
        $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
        $inputs['created_by'] = auth()->user()->id;
        $inputs['payment_for'] = $contact_id;
        $inputs['business_id'] = $request->session()->get('business.id');
        $inputs['linked_customer_statement'] = $statement->id;
        $inputs['cheque_date'] = ! empty($request->cheque_date) ? $this->transactionUtil->uf_date($request->cheque_date) : null;

        $prefix_type = 'sell_payment';
        $ref_count   = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

        //Generate reference number
        $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
        $inputs['payment_ref_no'] = $payment_ref_no;

        if (! empty($request->input('account_id'))) {
            $inputs['account_id'] = $request->input('account_id');
        }

        //Upload documents if added
        $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

        $inputs['paid_in_type'] = 'customer_statement';
        $due_payment_type       = 'sell';

        DB::beginTransaction();

        $contact           = Contact::findOrFail($contact_id);
        $post_dated        = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
        $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');
        if (! empty($inputs['update_post_dated_cheque'])) {
            $inputs['related_account_id'] = $request->input('account_id');

            if ($due_payment_type == 'sell_return') {
                $inputs['account_id'] = $issued_post_dated;
            } else {
                $inputs['account_id'] = $post_dated;
            }
        }

        $parent_payment = TransactionPayment::create($inputs);

        $inputs['transaction_type'] = $due_payment_type;

        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();
        $account_payable_id = ! empty($account_payable) ? $account_payable->id : 0;

        $account_transaction_data = [
            'contact_id'               => $contact_id,
            'amount'                   => $parent_payment->amount,
            'account_id'               => $parent_payment->account_id,
            'type'                     => 'credit',
            'operation_date'           => $parent_payment->paid_on,
            'created_by'               => Auth::user()->id,
            'transaction_payment_id'   => $parent_payment->id,
            'note'                     => null,
            'post_dated_cheque'        => $request->post_dated_cheque,
            'update_post_dated_cheque' => $request->update_post_dated_cheque,
        ];

        $location_id = BusinessLocation::where('business_id', $business_id)->first();

        $account_transaction_data['account_id'] = $request->account_id;

        $account_transaction_data['type'] = 'debit';
        if (! empty($inputs['update_post_dated_cheque'])) {
            $account_transaction_data['related_account_id'] = $request->input('account_id');
            $account_transaction_data['account_id']         = $post_dated;
        }

        AccountTransaction::createAccountTransaction($account_transaction_data);

        $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();
        $account_receivable_id = ! empty($account_receivable) ? $account_receivable->id : 0;

        $account_transaction_data['account_id'] = $account_receivable_id;
        $account_transaction_data['type'] = 'credit';
        $account_transaction_data['sub_type'] = 'ledger_show';

        AccountTransaction::createAccountTransaction($account_transaction_data);

        $account_transaction_data['contact_id'] = $contact_id;
        $account_transaction_data['sub_type'] = 'payment';

        ContactLedger::createContactLedger($account_transaction_data, 'Customer Statement');

        //Distribute above payment among unpaid transactions
        $this->transactionUtil->payCustomerStatementAtOnce($parent_payment, $transaction_ids);

        DB::commit();

        /*
         |----------------------------------------------------------------------
         | S637-2: Payment Received SMS for statement payments.
         |----------------------------------------------------------------------
         |
         | Customer Statements -> List Customer Statement -> Action ->
         | Payment Due Amount / Add Payment lands here, and this method had no
         | call to the notification layer at all - so no SMS was sent and none of
         | the transaction details reached the customer.
         |
         | The two figures the customer is told are the ones S637 asks for:
         |
         |   Paid Amount    = $parent_payment->amount, the amount entered on the
         |                    form. Not the sum distributed across invoices by
         |                    payCustomerStatementAtOnce() above, which can differ
         |                    when the payment does not settle every invoice
         |                    exactly.
         |
         |   Ledger Balance = read AFTER DB::commit(), so it is what REMAINS. It
         |                    comes from contact_ledgers via the Customers
         |                    module's own calculation - the same source every
         |                    Customers screen shows - rather than from
         |                    ContactUtil::getCustomerBalance(), which reads
         |                    transactions/transaction_payments and does not agree
         |                    with it. That mismatch was S637-1.
         |
         | Delegated to CustomerPaymentActionService so this message is built
         | exactly like the Bulk Payment and Pay Due ones. Sent after the commit
         | and inside its own try/catch: a dead SMS gateway must never roll back
         | or fail a payment that has already saved.
         */
        try {
            $statement_customer = \Modules\Customers\Entities\Customer::withoutGlobalScopes()
                ->where('business_id', $business_id)
                ->find($contact_id);

            if (! empty($statement_customer)) {
                app(\Modules\Customers\Services\CustomerPaymentActionService::class)
                    ->sendCustomerPaymentReceivedNotification(
                        (int) $business_id,
                        $statement_customer,
                        (float) $parent_payment->amount,
                        (string) $parent_payment->paid_on,
                        $parent_payment->payment_ref_no,
                        'Customer Statement Payment'
                    );
            }
        } catch (\Throwable $notification_exception) {
            Log::error('S637-2: Payment Received notification failed after a saved statement payment.', [
                'business_id' => $business_id,
                'contact_id'  => $contact_id,
                'payment_id'  => $parent_payment->id ?? null,
                'message'     => $notification_exception->getMessage(),
                'file'        => $notification_exception->getFile(),
                'line'        => $notification_exception->getLine(),
            ]);
        }

        $output = [
            'success' => true,
            'msg'     => __('purchase.payment_added_success'),
        ];
    } catch (\Exception $e) {
        DB::rollBack();

        Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

        $output = [
            'success' => false,
            'msg'     => __('messages.something_went_wrong'),
        ];
    }

    return redirect()->back()->with(['status' => $output]);
}


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function edit($id)
    {

        $business_id = $this->currentBusinessId();
        $transaction = Transaction::where('business_id', $business_id)->findOrFail($id);

        $customer_references = CustomerReference::where('business_id', $business_id)
            ->where('contact_id', $transaction->contact_id)
            ->pluck('reference', 'reference');

        return view('customers::customer_statement.edit')->with(compact('transaction', 'customer_references'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {

        try {

            $data = [

                'order_no'     => $request->order_no,

                'order_date'   => ! empty($request->order_date) ? Carbon::parse($request->order_date)->format('Y-m-d') : null,

                'customer_ref' => $request->customer_ref,

            ];

            Transaction::where('business_id', $this->currentBusinessId())
                ->where('id', $id)
                ->update($data);

            $output = [

                'success' => 1,

                'msg'     => __('message.success'),

            ];
        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => 0,

                'msg'     => __('messages.something_went_wrong'),

            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function destroyPayments($id)
    {

        if (request()->ajax()) {
            try {
                DB::beginTransaction();
                $business_id = $this->currentBusinessId();
                $customer_statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);

                $changed_msg = "Payments for Customer Statement #" . $customer_statement->statement_no . " has been deleted by " . auth()->user()->username;

                $activity               = new Activity();
                $activity->log_name     = "Customer Statement";
                $activity->description  = "delete";
                $activity->subject_id   = $id;
                $activity->subject_type = "App\CustomerStatement";
                $activity->causer_id    = auth()->user()->id;
                $activity->causer_type  = 'App\User';
                $activity->properties   = $changed_msg;
                $activity->created_at   = date('Y-m-d H:i');
                $activity->updated_at   = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

                $this->transactionUtil->deleteStatementBulkPayments($id);

                DB::commit();

                $output = [
                    'success' => true,
                    'msg'     => __("lang_v1.success"),
                ];
            } catch (\Exception $e) {
                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                DB::rollback();

                $output = [
                    'success' => false,
                    'msg'     => __("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }

    public function destroy($id)
    {
        if (!request()->ajax()) {
            abort(404);
        }

        $business_id = $this->currentBusinessId();

        try {
            DB::transaction(function () use ($id, $business_id) {
                $customer_statement = CustomerStatement::where('business_id', $business_id)
                    ->lockForUpdate()
                    ->findOrFail($id);

                $changed_msg = "Customer Statement #" . $customer_statement->statement_no
                    . " has been deleted by " . optional(auth()->user())->username;

                $activity = new Activity();
                $activity->log_name = 'Customer Statement';
                $activity->description = 'delete';
                $activity->subject_id = $id;
                $activity->subject_type = 'App\CustomerStatement';
                $activity->causer_id = optional(auth()->user())->id;
                $activity->causer_type = 'App\User';
                $activity->properties = $changed_msg;
                $activity->created_at = now();
                $activity->updated_at = now();
                $activity->save();

                CustomerStatementDetail::where('business_id', $business_id)
                    ->where('statement_id', $id)
                    ->delete();
                $customer_statement->delete();
            });

            return [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $e) {
            Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    /**
     * print the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function getMinimumDate(Request $r)
    {
        $business_id = $this->currentBusinessId();
        $customer_id = (int) $r->get('id');
        Contact::where('business_id', $business_id)->findOrFail($customer_id);

        $customer_date = CustomerStatement::where('business_id', $business_id)
            ->where('customer_id', $customer_id)
            ->orderByDesc('date_to')
            ->first();
        // logger('error --->'.json_encode($customer_date));
        return response()->json(['date' => $customer_date ? $customer_date->date_to : null]);
    }

    public function rePrint($id)
    {

        $business_id = $this->currentBusinessId();
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);

        $reprint_no = $statement->reprint_no;

        $statement->reprint_no = $reprint_no + 1;
        $statement->save();

        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;

        $end_date = $statement->date_to;

        $statement_details = CustomerStatementDetail::where('business_id', $business_id)
            ->where('statement_id', $id)
            ->get();

        $contact_id                        = $contact->id;
        $business_id                       = (int) $contact->business_id;
        $ledger_details                    = [];
        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $for_pdf = 1;

        $reprint = 1;

        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);

        $font_settings_record = ReportConfiguration::where('business_id', $business_id)
            ->where('name', 'customer_statement_report')
            ->first();

        $font_settings = !empty($font_settings_record)
            ? json_decode($font_settings_record->configurations, true)
            : [];
        $customer_statement_report = is_array($font_settings) ? $font_settings : [];
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();

        return view('customers::customer_statement.print')->with(compact(

            'logo',

            'contact',

            'ledger_details',

            'business_details',

            'for_pdf',

            'location_details',

            'statement_details',

            'statement',

            'reprint',
            'start_date',
            'end_date',
            'reprint_no',
            'font_settings',
            'customer_statement_report',
            'reports_footer'

        ));
    }

    public function exportExcel($id)
    {

        $business_id = $this->currentBusinessId();
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);

        $reprint_no = $statement->reprint_no;

        $statement->reprint_no = $reprint_no + 1;
        $statement->save();

        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;

        $end_date = $statement->date_to;

        $statement_details = CustomerStatementDetail::where('business_id', $business_id)
            ->where('statement_id', $id)
            ->get();

        $contact_id                        = $contact->id;
        $business_id                       = (int) $contact->business_id;
        $ledger_details                    = [];
        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $for_pdf = 1;

        $reprint = 1;

        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);

        $response = MatExcel::download(new CustomerStatementExport(
            $logo,
            $contact,
            $ledger_details,
            $business_details,
            $for_pdf,
            $location_details,
            $statement_details,
            $statement,
            $reprint,
            $start_date,
            $end_date,
            $reprint_no
        ), "CustomerStatement.xls");

        return $response;
    }

    public function exportExcelPmt($id)
    {

        $business_id = $this->currentBusinessId();
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);

        $reprint_no = $statement->reprint_no;

        $statement->reprint_no = $reprint_no + 1;
        $statement->save();

        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;

        $end_date    = $statement->date_to;
        $contact_id  = $contact->id;
        $business_id = (int) $contact->business_id;

        $statement_details = $this->paymentInclusiveStatementDetails($statement, $contact);

        $ledger_details                    = [];
        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $for_pdf = 1;

        $reprint = 1;

        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);

        $response = MatExcel::download(new CustomerStatementPmtExport(
            $logo,
            $contact,
            $ledger_details,
            $business_details,
            $for_pdf,
            $location_details,
            $statement_details,
            $statement,
            $reprint,
            $start_date,
            $end_date,
            $reprint_no
        ), "CustomerStatementPayment.xls");

        return $response;
    }

    public function rePrintPmt($id)
    {

        $business_id = $this->currentBusinessId();
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);

        $reprint_no = $statement->reprint_no;

        $statement->reprint_no = $reprint_no + 1;
        $statement->save();

        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;

        $end_date    = $statement->date_to;
        $contact_id  = $contact->id;
        $business_id = (int) $contact->business_id;

        $statement_details = $this->paymentInclusiveStatementDetails($statement, $contact);

        $ledger_details                    = [];
        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $for_pdf = 1;

        $reprint = 1;

        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);

        $font_settings_record = ReportConfiguration::where('business_id', $business_id)
            ->where('name', 'customer_statement_report')
            ->first();

        $font_settings = !empty($font_settings_record)
            ? json_decode($font_settings_record->configurations, true)
            : [];
        $customer_statement_report = is_array($font_settings) ? $font_settings : [];
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();

        return view('customers::customer_statement.print-pmt')->with(compact(

            'logo',

            'contact',

            'ledger_details',

            'business_details',

            'for_pdf',

            'location_details',

            'statement_details',

            'statement',

            'reprint',
            'start_date',
            'end_date',
            'reprint_no',
            'font_settings',
            'customer_statement_report',
            'reports_footer'

        ));
    }

    public function showPmt($id)
    {
        $business_id = $this->currentBusinessId();
        $statement = CustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);
        $start_date = $statement->date_from;
        $end_date = $statement->date_to;
        $contact_id = $contact->id;

        $statement_details = $this->paymentInclusiveStatementDetails($statement, $contact);

        $ledger_details = ['opening_balance' => 0];
        if ($contact->type === 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details'] = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        } elseif ($contact->type === 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details'] = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $for_pdf = 1;
        $logo = CustomerStatementLogo::where('business_id', $business_id)->find($statement->logo);
        $font_settings = $this->customerStatementReportSettings($business_id);
        $customer_statement_report = $font_settings;
        $paid_customer_statement = TransactionPayment::where('business_id', $business_id)
            ->where('linked_customer_statement', $id)
            ->count();
        $pacakge_details = $this->activePackageDetails($business_id);

        return view('customers::customer_statement.show-pmt')->with(compact(
            'id', 'logo', 'contact', 'ledger_details', 'business_details', 'for_pdf',
            'location_details', 'statement_details', 'statement', 'font_settings',
            'customer_statement_report', 'paid_customer_statement', 'pacakge_details',
        ));
    }

    public function __getPayments($start_date, $end_date, $business_id, $contact_id)
    {

        $pmts = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('transaction_payments.business_id', $business_id)
            ->whereNull('transaction_payments.deleted_at')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($query) {
                $query->whereNull('transaction_payments.transaction_id')
                    ->orWhere(function ($query) {
                        $query->whereNotIn('transactions.type', ['security_deposit', 'refund_security_deposit', 'security_deposit_refund']);
                    });
            })
            ->where('transaction_payments.payment_for', $contact_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            ->withTrashed()
            ->select([
                'transactions.id',
                'transactions.business_id',
                DB::raw('transaction_payments.id as payment_row'),
                'transaction_payments.paid_on as date',
                'business_locations.name as location',
                'transaction_payments.payment_ref_no as invoice_no',
                DB::raw('transaction_payments.account_id as customer_reference'), //customer_reference
                DB::raw('"" as order_no'),
                'transaction_payments.paid_on as order_date',
                DB::raw('"" as product'),
                DB::raw('0 as unit_price'),
                DB::raw('0 as qty'),
                DB::raw('"" as vehicle_number'),
                DB::raw('"" as route_name'),
                'transaction_payments.amount as invoice_amount',
                DB::raw('0 as due_amount'),
                DB::raw('"payment" as type'),
            ]);

        $txnResult = $pmts;
        return $txnResult;
    }
    public function __getCustomerPayments($start_date, $end_date, $business_id, $contact_id)
    {
        $customer_payments = CustomerPayment::leftjoin('settlements', 'customer_payments.settlement_no', 'settlements.id')
            ->leftjoin('transactions', 'transactions.invoice_no', 'settlements.settlement_no')
            ->leftjoin('business_locations', 'business_locations.id', 'transactions.location_id')
            ->where('customer_payments.business_id', $business_id)
            ->whereDate('settlements.transaction_date', '>=', $start_date)
            ->whereDate('settlements.transaction_date', '<=', $end_date)
            ->where('customer_payments.customer_id', $contact_id)
            ->select([
                'transactions.id',
                'transactions.business_id',
                DB::raw('customer_payments.id as payment_row'),
                'settlements.transaction_date as date',
                'business_locations.name as location',
                'settlements.settlement_no as invoice_no',
                DB::raw('customer_payments.bank_name as customer_reference'),
                DB::raw('"" as order_no'),
                'settlements.transaction_date as order_date',
                DB::raw('"" as product'),
                DB::raw('0 as unit_price'),
                DB::raw('0 as qty'),
                DB::raw('"" as vehicle_number'),
                DB::raw('"" as route_name'),
                'customer_payments.amount as invoice_amount',
                DB::raw('0 as due_amount'),
                DB::raw('"customer_payment" as type'),
            ])->groupBy('customer_payments.id');

        $txnResult = $customer_payments;
        return $txnResult;
    }

    public function getStatementHeader(Request $request, $statement_no)
    {

        $contact_id = $request->customer_id;

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $contact = Contact::findOrFail($contact_id);

        $business_id = (int) $contact->business_id;

        $ledger_details = [];

        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $opening_balance = Transaction::where('contact_id', $contact_id)->where('type', 'opening_balance')->where('payment_status', 'due')->sum('final_total');

        $for_pdf = 1;

        return view('customers::customer_statement.partials.print_statement_header')->with(compact('contact', 'ledger_details', 'business_details', 'for_pdf', 'location_details', 'statement_no', 'opening_balance'))->render();
    }

    public function getCustomerStatementNo(Request $request)
    {

        $customer_id = $request->customer_id;

        $customer_settings = CustomerStatementSetting::where('customer_id', $customer_id)->first();

        if (! empty($customer_settings)) {

            $starting_no = $customer_settings->starting_no;
        } else {

            $starting_no = 1;
        }

        $count = CustomerStatement::where('customer_id', $customer_id)->count();

        $statement_no = $starting_no + $count;

        $header = (string) $this->getStatementHeader($request, $statement_no);

        return ['statement_no' => $statement_no, 'header' => $header];
    }

    public function getCustomerStatementListPmt(Request $request)
    {

        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        if (request()->ajax()) {
            // ini_set('memory_limit', -1);
            // ini_set('max_execution_time', '0');

            $statementTotals = CustomerStatementDetail::query()
                ->where('business_id', $business_id)
                ->select('statement_id')
                ->selectRaw('SUM(invoice_amount) as statement_amount')
                ->groupBy('statement_id');
            $paymentCounts = TransactionPayment::query()
                ->where('business_id', $business_id)
                ->whereNotNull('linked_customer_statement')
                ->select('linked_customer_statement')
                ->selectRaw('COUNT(*) as paid_statement_payments')
                ->groupBy('linked_customer_statement');

            $query = CustomerStatement::with(['contact', 'user', 'location'])
                ->leftJoinSub($statementTotals, 'statement_totals', function ($join) {
                    $join->on('statement_totals.statement_id', '=', 'customer_statements.id');
                })
                ->leftJoinSub($paymentCounts, 'statement_payment_counts', function ($join) {
                    $join->on('statement_payment_counts.linked_customer_statement', '=', 'customer_statements.id');
                })
                ->where('customer_statements.business_id', $business_id)
                ->select('customer_statements.*')
                ->selectRaw('COALESCE(statement_totals.statement_amount, 0) as statement_amount')
                ->selectRaw('COALESCE(statement_payment_counts.paid_statement_payments, 0) as paid_statement_payments');
            if (! empty($request->start_date)) {
                $query->whereDate('customer_statements.date_from', '>=', $request->start_date);
            }

            if (! empty($request->end_date)) {
                $query->whereDate('customer_statements.date_to', '<=', $request->end_date);
            }

            if (! empty($request->printed_start)) {
                $query->whereDate('customer_statements.print_date', '>=', $request->printed_start);
            }

            if (! empty($request->printed_end)) {
                $query->whereDate('customer_statements.print_date', '<=', $request->printed_end);
            }

            if (! empty($request->location_id)) {
                $query->where('customer_statements.location_id', $request->location_id);
            }

            if (! empty($request->customer_id)) {
                $query->where('customer_statements.customer_id', $request->customer_id);
            }

            $pacakge_details = $this->activePackageDetails($business_id);

            $fuel_tanks = Datatables::of($query)
                ->addColumn('action', function ($row) use ($pacakge_details) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">' .
                    __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.show_pmt', [$row->id]) . '" class="btn-modal" data-container=".customer_statement_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.reprint_pmt', [$row->id]) . '" class="reprint_statement"><i class="fa fa-print" aria-hidden="true"></i> ' . __("contact.print") . '</a></li>';
                    $html .= '<li><a href="' . route('customers.customer_statement.export_excel_pmt', [$row->id]) . '" ><i class="fa fa-print" aria-hidden="true"></i> ' . __("contact.download_excel") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.reprint_pmt', [$row->id]) . '" class="pdf_statement"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> ' . __("business.pdf") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.reprint_pmt', [$row->id]) . '" class="email_statement"><i class="fa fa-envelope" aria-hidden="true"></i> ' . __("business.email") . '</a></li>';
                    $paid_customer_statement = (int) $row->paid_statement_payments;

                    if ($paid_customer_statement > 0) {
                        if ($this->userCan('contact.delete_statement_payment') && (! empty($pacakge_details['contact.delete_statement_payment']) || ! array_key_exists('contact.delete_statement_payment', $pacakge_details))) {
                            $html .= '<li><a data-href="' . route('customers.customer_statement.delete_payment', [$row->id]) . '" class="delete_customer_statement"><i class="fa fa-trash"></i>' . __("lang_v1.delete_payments") . '</a></li>';
                        }
                    } else {
                        if ($this->userCan('contact.delete_customer_statement') && (! empty($pacakge_details['contact.delete_customer_statement']) || ! array_key_exists('contact.delete_customer_statement', $pacakge_details))) {
                            $html .= '<li><a data-href="' . url('/customers/customer-statement/' . $row->id) . '" class="delete_customer_statement"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
                        }
                    }
                    return $html;
                })
                ->addColumn('customer', function ($row) {
                    return $row->contact ? $row->contact->name : null;
                })
                ->addColumn('username', function ($row) {
                    return $row->user ? $row->user->username : null;
                })
                ->addColumn('location', function ($row) {
                    return $row->location ? $row->location->name : null;
                })
                ->addColumn('amount', function ($row) {
                    $amount = (float) $row->statement_amount;
                    return '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $amount . '">' . $this->commonUtil->num_f($amount) . '</span>';
                })
                ->removeColumn('id');

            return $fuel_tanks->rawColumns(['action', 'amount'])->make(true);
        }

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->pluck('name', 'id');

        $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        $enable_separate_customer_statement_no = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_separate_customer_statement_no');

        $help_explanations = HelpExplanation::pluck('value', 'help_key');

        $statement_no = CustomerStatement::where('business_id', $business_id)->count();

        $logos = CustomerStatementLogo::where('business_id', $business_id)->select('*')->pluck('image_name', 'id');

        return view('customers::customer_statement.index-payments')->with(compact('logos', 'customers', 'enable_separate_customer_statement_no', 'business_locations', 'statement_no', 'help_explanations'));
    }

    public function getCustomerStatementList(Request $request)
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

        if ($request->ajax()) {
            $paymentCounts = TransactionPayment::query()
                ->where('business_id', $business_id)
                ->whereNotNull('linked_customer_statement')
                ->select('linked_customer_statement')
                ->selectRaw('COUNT(*) as paid_statement_payments')
                ->groupBy('linked_customer_statement');

            $query = CustomerStatement::with([
                'contact',
                'user',
                'location',
                'details.transaction',
            ])
                ->leftJoin('contacts', 'contacts.id', '=', 'customer_statements.customer_id')
                ->leftJoin('vat_customer_statements', 'vat_customer_statements.id', 'customer_statements.linked_vat_statement')
                ->leftJoin('users as u', 'u.id', 'customer_statements.converted_by')
                ->leftJoinSub($paymentCounts, 'statement_payment_counts', function ($join) {
                    $join->on('statement_payment_counts.linked_customer_statement', '=', 'customer_statements.id');
                })
                ->where('customer_statements.business_id', $business_id)
                ->select(
                    'customer_statements.*',
                    'contacts.name as customer_name',
                    'u.username as user_converted',
                    'vat_customer_statements.statement_no as vat_statement',
                    'vat_customer_statements.print_date as vat_date',
                    DB::raw('COALESCE(statement_payment_counts.paid_statement_payments, 0) as paid_statement_payments')
                );

            // Apply filters
            // Statement date range filter: include any statements that OVERLAP
            // the selected range, not only those fully inside it.
            if (! empty($request->start_date) && ! empty($request->end_date)) {
                $start = $request->start_date;
                $end   = $request->end_date;

                $query->where(function ($q) use ($start, $end) {
                    $q->whereDate('customer_statements.date_to', '>=', $start)
                      ->whereDate('customer_statements.date_from', '<=', $end);
                });
            } else {
                if (! empty($request->start_date)) {
                    $query->whereDate('customer_statements.date_to', '>=', $request->start_date);
                }
                if (! empty($request->end_date)) {
                    $query->whereDate('customer_statements.date_from', '<=', $request->end_date);
                }
            }
            if (! empty($request->printed_start)) {
                $query->whereDate('customer_statements.print_date', '>=', $request->printed_start);
            }
            if (! empty($request->printed_end)) {
                $query->whereDate('customer_statements.print_date', '<=', $request->printed_end);
            }
            if (! empty($request->location_id)) {
                $query->where('customer_statements.location_id', $request->location_id);
            }
            if (! empty($request->customer_id)) {
                $query->where('customer_statements.customer_id', $request->customer_id);
            }

            // Apply customer type filtering
            if (! empty($request->customer_type) && $request->customer_type != 'all') {
                $query->where('contacts.type', $request->customer_type);
            }

            // Apply search filtering
            if (! empty($request->search_term)) {
                $search_term = $request->search_term;
                $query->where(function($q) use ($search_term) {
                    $q->where('customer_statements.statement_no', 'like', '%' . $search_term . '%')
                      ->orWhere('contacts.name', 'like', '%' . $search_term . '%');
                });
            }

            $package_details = $this->activePackageDetails($business_id);

            $fuel_tanks = Datatables::of($query)
                ->addColumn('action', function ($row) use ($package_details) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                    __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if ($row->is_converted == 0) {
                        $html .= '<li><a href="#" data-href="' . action("\Modules\Vat\Http\Controllers\CustomerStatementController@convertVAT", [$row->id]) . '" class="btn-convert"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("contact.convert_vat_statement") . '</a></li>';
                    }

                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.list_show', [$row->id]) . '" class="btn-modal-column" data-container=".customer_statement_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';

                    // Get transactions from preloaded relations
                    // NOTE: Some old details may not have a linked transaction -> filter out nulls first
                    $transactions = $row->details->pluck('transaction')->filter();
                    $has_due      = $transactions->contains(function ($t) {
                        return in_array($t->payment_status, ['due', 'partial']);
                    });

                    if ($has_due) {
                        $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.pay_total', [$row->id]) . '" class="btn-modal" data-container=".customer_statement_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("contact.pay_total_statement") . '</a></li>';
                    }

                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.list_reprint', [$row->id]) . '" class="reprint_statement"><i class="fa fa-print" aria-hidden="true"></i> ' . __("contact.print") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.list_export_excel', [$row->id]) . '" 
					class="export_list_statement"><i class="fa fa-print" aria-hidden="true"></i> ' . __("contact.export_excel") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.list_reprint', [$row->id]) . '" class="pdf_statement"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> ' . __("business.pdf") . '</a></li>';
                    $html .= '<li><a href="#" data-href="' . route('customers.customer_statement.list_reprint', [$row->id]) . '" class="email_statement"><i class="fa fa-envelope" aria-hidden="true"></i> ' . __("business.email") . '</a></li>';
                    // Permission-based delete options
                    $paid_customer_statement = (int) $row->paid_statement_payments;

                    if ($paid_customer_statement > 0) {
                        if ($this->userCan('contact.delete_statement_payment') &&
                            (! isset($package_details['contact.delete_statement_payment']) || $package_details['contact.delete_statement_payment'])) {
                            $html .= '<li><a data-href="' . route('customers.customer_statement.delete_payment', [$row->id]) . '" class="delete_customer_statement"><i class="fa fa-trash"></i>' . __("lang_v1.delete_payments") . '</a></li>';
                        }
                    } else {
                        if ($this->userCan('contact.delete_customer_statement') &&
                            (! isset($package_details['contact.delete_customer_statement']) || $package_details['contact.delete_customer_statement'])) {
                            $html .= '<li><a data-href="' . url('/customers/customer-statement/' . $row->id) . '" class="delete_customer_statement"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
                        }
                    }

                    return $html;
                })
                ->addColumn('customer', fn($row) => $row->customer_name ?? ($row->contact->name ?? null))
                ->addColumn('description', function ($row) {
                    if ($row->is_converted == 1) {
                        $html = "<span class='badge bg-success'>" . __('contact.is_converted') . "</span><br>";
                        $html .= "<span>" . __('contact.created_statement') . " <b>{$row->vat_statement}</b> " . __('contact.on') . " <b>" . $this->commonUtil->format_date($row->vat_date) . "</b></span><br>";
                        $html .= "<span>" . $row->user_converted . "</span><br>";
                        return $html;
                    }
                    return '';
                })
                ->addColumn('username', fn($row) => $row->user->username ?? null)
                ->addColumn('amount', function ($row) {
                    $sum = $row->details->sum('invoice_amount');
                    return '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $sum . '">' . $this->commonUtil->num_f($sum) . '</span>';
                })
                ->addColumn('payment_status', function ($row) {
                    // Some statement details may not have a related transaction,
                    // so we filter out null values before checking payment_status.
                    $transactions = $row->details->pluck('transaction')->filter();
                    $total        = $transactions->count();

                    // If there are no valid transactions, treat as "paid" (no outstanding dues)
                    if ($total === 0) {
                        return "<span class='badge bg-success'>" . __('vat::lang.paid') . "</span>";
                    }

                    $due = $transactions->filter(function ($t) {
                        return in_array($t->payment_status, ['due', 'partial']);
                    })->count();

                    if ($due === 0) {
                        return "<span class='badge bg-success'>" . __('vat::lang.paid') . "</span>";
                    } elseif ($due === $total) {
                        return "<span class='badge bg-danger'>" . __('vat::lang.due') . "</span>";
                    } else {
                        return "<span class='badge bg-warning'>" . __('vat::lang.partial') . "</span>";
                    }
                })
                ->removeColumn('id');

            return $fuel_tanks->rawColumns(['action', 'amount', 'description', 'payment_status'])->make(true);
        }
    }



    private function getLatestVehicleFromReferences(int $contactId, ?int $businessId = null): ?string
{
    return DB::table('customer_references')
        ->when($businessId, fn($q) => $q->where('business_id', $businessId))
        ->where('contact_id', $contactId)        // ملاحظة: العمود اسمه contact_id
        ->orderByDesc('date')
        ->orderByDesc('id')
        ->value('reference');                    // هذا هو رقم المركبة
}

	public function showCustomerStatement($id)
{
    try {
        // dd('masok');
        $customerStatement = CustomerStatement::with([
                'contact',
                'user',
                'location',
                'details.transaction',
            ])
            ->leftJoin('vat_customer_statements', 'vat_customer_statements.id', 'customer_statements.linked_vat_statement')
            ->leftJoin('users as u', 'u.id', 'customer_statements.converted_by')
            ->select(
                'customer_statements.*',
                'u.username as user_converted',
                'vat_customer_statements.statement_no as vat_statement',
                'vat_customer_statements.print_date as vat_date'
            )
            ->where('customer_statements.business_id', $this->currentBusinessId())
            ->where('customer_statements.id', $id)
            ->firstOrFail();

        // ===== Header =====
        $row = [];
        $row['date_printed'] = request()->get('print_date');
        $row['date_from']    = request()->get('date_from');
        $row['date_to']      = request()->get('date_to');
        $row['customer']     = optional($customerStatement->contact)->name ?? null;

        $descHtml = '';
        if ((int) $customerStatement->is_converted === 1) {
            $descHtml  = "<span class='badge bg-success'>" . __('contact.is_converted') . "</span><br>";
            $descHtml .= "<span>" . __('contact.created_statement') . " <b>{$customerStatement->vat_statement}</b> "
                       . __('contact.on') . " <b>" . $this->commonUtil->format_date($customerStatement->vat_date) . "</b></span><br>";
            $descHtml .= "<span>" . e($customerStatement->user_converted) . "</span><br>";
        }
        $row['description']  = $descHtml;
        $row['added_by']     = optional($customerStatement->user)->username ?? '';

        $sum = (float) $customerStatement->details->sum('invoice_amount');
        $row['statement_no'] = $customerStatement->statement_no;
        $row['statement_amount'] =
            '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $sum . '">'
            . $this->commonUtil->num_f($sum) . '</span>';

        // حالة الدفع
        $transactions = $customerStatement->details->pluck('transaction')->filter();
        $total = $transactions->count();
        $dueCnt = $transactions->filter(fn($t) => in_array($t->payment_status, ['due', 'partial'], true))->count();

        if ($total === 0 || $dueCnt === 0) {
            $paymentStatus = "<span class='badge bg-success'>" . __('vat::lang.paid') . "</span>";
        } elseif ($dueCnt === $total) {
            $paymentStatus = "<span class='badge bg-danger'>" . __('vat::lang.due') . "</span>";
        } else {
            $paymentStatus = "<span class='badge bg-warning'>" . __('vat::lang.partial') . "</span>";
        }
        $row['payment_status'] = $paymentStatus;

        $visibleCols = array_filter(
            explode(',', (string) request()->get('columns', ''))
        );

        $contact          = Contact::findOrFail($customerStatement->customer_id);
        $logo             = CustomerStatementLogo::find($customerStatement->logo);
        $business_details = $this->businessUtil->getDetails($contact->business_id);
        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();
        $for_pdf          = 1;

        // === Helpers
        $sanitizeScalar = function ($v, int $max = 120) {
            if ($v === null || $v === '') return '';
            $decoded = json_decode($v, true);
            $val = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? implode(' + ', array_map(fn($x) => (string)$x, $decoded))
                : (string) $v;
            $val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val);
            if (mb_strlen($val) > $max) $val = mb_substr($val, 0, $max) . '…';
            return $val;
        };

        // ===== 1) اجلب Vehicle No من customer_references (reference) بحسب contact_id
        $vehFromRef = $this->getLatestVehicleFromReferences(
            $customerStatement->customer_id,
            $business_details->id ?? null
        ) ?? '';

$fromReq = request()->get('date_from');
$toReq   = request()->get('date_to');

$start = Carbon::parse($fromReq ?: ($customerStatement->date_from ?? now()->startOfYear()))
          ->startOfDay();
$end   = Carbon::parse($toReq   ?: ($customerStatement->date_to   ?? now()))
          ->endOfDay();

        $rawLines = DB::table('customer_statement_details')
            ->where('statement_id', $id)
            ->whereBetween(
                DB::raw("DATE(COALESCE(order_date, date))"),
                [$start->toDateString(), $end->toDateString()]
            )
            ->selectRaw("
                DATE_FORMAT(COALESCE(date, order_date), '%Y-%m-%d') as date,
                location,
                invoice_no,
                route_name,
                TRIM(COALESCE(vehicle_number, '')) as vehicle_no,
                customer_reference,
                order_no as customer_po_no,
                DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d') as voucher_order_date,
                product,
                unit_price,
                SUM(qty) as qty,
                SUM(invoice_amount) as invoice_amount,
                SUM(due_amount) as due_amount
            ")
            ->groupBy([
                DB::raw("DATE_FORMAT(COALESCE(date, order_date), '%Y-%m-%d')"),
                'location',
                'invoice_no',
                'route_name',
                'vehicle_no',
                'customer_reference',
                'customer_po_no',
                DB::raw("DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d')"),
                'product',
                'unit_price',
            ])
            ->orderBy('date', 'asc')
            ->get();

        $billLines = collect($rawLines)->map(function ($r) use ($sanitizeScalar, $vehFromRef) {
            $veh = trim((string)($r->vehicle_no ?? ''));
            if ($veh === '') {
                $veh = $vehFromRef !== '' ? $vehFromRef : 'N/A';
            }

            return [
                'date'               => (string) ($r->date ?? ''),
                'location'           => (string) ($r->location ?? ''),
                'invoice_no'         => (string) ($r->invoice_no ?? ''),
                'route_name'         => (string) ($r->route_name ?? ''),
                'vehicle_no'         => $veh,
                'customer_reference' => (string) ($r->customer_reference ?? ''),
                'customer_po_no'     => (string) ($r->customer_po_no ?? ''),
                'voucher_order_date' => (string) ($r->voucher_order_date ?? ''),
                'product'            => $sanitizeScalar($r->product),
                'qty'                => (string) ($r->qty ?? ''),
                'unit_price'         => (string) ($r->unit_price ?? ''),
                'invoice_amount'     => (float)  ($r->invoice_amount ?? 0),
                'due_amount'     => (float)  ($r->due_amount ?? 0),
            ];
        });

        $customer_statement_report = $this->customerStatementReportSettings((int) $contact->business_id);

        return view('customers::customer_statement.show-detail-lcs')->with(compact(
            'row',
            'visibleCols',
            'logo',
            'business_details',
            'location_details',
            'contact',
            'for_pdf',
            'billLines',
            'customer_statement_report'
        ));
    } catch (\Throwable $e) {
        if (request()->ajax()) {
            return response('Error: ' . $e->getMessage(), 500);
        }
        throw $e;
    }
}

	
	public function rePrintListCustomerState($id)
{
    try {
        // 1) اجلب البيان والعلاقات
        $cs = CustomerStatement::with([
                'contact',
                'user',
                'location',
                'details.transaction',
            ])
            ->leftJoin('vat_customer_statements', 'vat_customer_statements.id', 'customer_statements.linked_vat_statement')
            ->leftJoin('users as u', 'u.id', 'customer_statements.converted_by')
            ->select(
                'customer_statements.*',
                'u.username as user_converted',
                'vat_customer_statements.statement_no as vat_statement',
                'vat_customer_statements.print_date as vat_date'
            )
            ->where('customer_statements.business_id', $this->currentBusinessId())
            ->where('customer_statements.id', $id)
            ->firstOrFail();

        // عدّاد إعادة الطباعة
        $reprint_no = (int)$cs->reprint_no;
        $cs->reprint_no = $reprint_no + 1;
        $cs->save();

        // 2) هيدر التقرير
        $row = [];
        $row['date_printed'] = request()->get('print_date');
        $row['date_from']    = request()->get('date_from');
        $row['date_to']      = request()->get('date_to');
        $row['customer']     = optional($cs->contact)->name ?? null;

        $descHtml = '';
        if ((int) $cs->is_converted === 1) {
            $descHtml  = "<span class='badge bg-success'>" . __('contact.is_converted') . "</span><br>";
            $descHtml .= "<span>" . __('contact.created_statement') . " <b>{$cs->vat_statement}</b> "
                       . __('contact.on') . " <b>" . $this->commonUtil->format_date($cs->vat_date) . "</b></span><br>";
            $descHtml .= "<span>" . e($cs->user_converted) . "</span><br>";
        }
        $row['description']  = $descHtml;
        $row['added_by']     = optional($cs->user)->username ?? '';

        $sum = (float) $cs->details->sum('invoice_amount');
        $row['statement_no']     = $cs->statement_no;
        $row['statement_amount'] = '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $sum . '">' . $this->commonUtil->num_f($sum) . '</span>';

        // حالة الدفع
        $transactions = $cs->details->pluck('transaction')->filter();
        $total  = $transactions->count();
        $dueCnt = $transactions->filter(fn($t) => in_array($t->payment_status, ['due','partial'], true))->count();
        if ($total === 0 || $dueCnt === 0) {
            $row['payment_status'] = "<span class='badge bg-success'>" . __('vat::lang.paid') . "</span>";
        } elseif ($dueCnt === $total) {
            $row['payment_status'] = "<span class='badge bg-danger'>" . __('vat::lang.due') . "</span>";
        } else {
            $row['payment_status'] = "<span class='badge bg-warning'>" . __('vat::lang.partial') . "</span>";
        }

        $visibleCols = array_filter(explode(',', (string) request()->get('columns', '')));

        // 3) بيانات ثابتة أخرى
        $contact          = Contact::findOrFail($cs->customer_id);
        $logo             = CustomerStatementLogo::find($cs->logo);
        $business_details = $this->businessUtil->getDetails($contact->business_id);
        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();
        $for_pdf          = 1;
        $reprint          = 1;

        // 4) اجلب رقم المركبة الافتراضي من customer_references.reference (على مستوى العميل)
        $vehFromRef = $this->getLatestVehicleFromReferences(
            $cs->customer_id,
            $business_details->id ?? null
        ) ?? '';

        // 5) دالة تنظيف بسيطة للنصوص
        $sanitizeScalar = function ($v, int $max = 120) {
            if ($v === null || $v === '') return '';
            $decoded = json_decode($v, true);
            $val = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? implode(' + ', array_map(fn($x) => (string)$x, $decoded))
                : (string)$v;
            $val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val);
            if (mb_strlen($val) > $max) $val = mb_substr($val, 0, $max) . '…';
            return $val;
        };


        $fromReq = request()->get('date_from');
$toReq   = request()->get('date_to');

$start = Carbon::parse($fromReq ?: ($cs->date_from ?? now()->startOfYear()))
          ->startOfDay();
$end   = Carbon::parse($toReq   ?: ($cs->date_to   ?? now()))
          ->endOfDay();

        // 6) سطور الفاتورة: نقرأ vehicle_number من التفاصيل إن وُجد، وإلا fallback إلى reference
        $rawLines = DB::table('customer_statement_details')
            ->where('statement_id', $id)
            ->whereBetween(
                DB::raw("DATE(COALESCE(order_date, date))"),
                [$start->toDateString(), $end->toDateString()]
            )
            ->selectRaw("
                DATE_FORMAT(COALESCE(date, order_date), '%Y-%m-%d') as date,
                location,
                invoice_no,
                route_name,
                TRIM(COALESCE(vehicle_number, '')) as vehicle_no,
                customer_reference,
                order_no as customer_po_no,
                DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d') as voucher_order_date,
                product,
                unit_price,
                SUM(qty) as qty,
                SUM(invoice_amount) as invoice_amount,
                SUM(due_amount) as due_amount
            ")
            ->groupBy([
                DB::raw("DATE_FORMAT(COALESCE(date, order_date), '%Y-%m-%d')"),
                'location',
                'invoice_no',
                'route_name',
                'vehicle_no',
                'customer_reference',
                'customer_po_no',
                DB::raw("DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d')"),
                'product',
                'unit_price',
            ])
            ->orderBy('date', 'asc')
            ->get();

        $billLines = collect($rawLines)->map(function ($r) use ($sanitizeScalar, $vehFromRef) {
            $veh = trim((string)($r->vehicle_no ?? ''));
            if ($veh === '') {
                $veh = $vehFromRef !== '' ? $vehFromRef : 'N/A';
            }
            return [
                'date'               => (string) ($r->date ?? ''),
                'location'           => (string) ($r->location ?? ''),
                'invoice_no'         => (string) ($r->invoice_no ?? ''),
                'route_name'         => (string) ($r->route_name ?? ''),
                'vehicle_no'         => $veh,
                'customer_reference' => (string) ($r->customer_reference ?? ''),
                'customer_po_no'     => (string) ($r->customer_po_no ?? ''),
                'voucher_order_date' => (string) ($r->voucher_order_date ?? ''),
                'product'            => $sanitizeScalar($r->product),
                'qty'                => (string) ($r->qty ?? ''),
                'unit_price'         => (string) ($r->unit_price ?? ''),
                'invoice_amount'     => (float)  ($r->invoice_amount ?? 0),
                'due_amount'         => (float)  ($r->due_amount ?? 0),
            ];
        })->sortBy('date')->values();

        // IS1483: explicit total for Balance Due footer in print preview.
        $total_balance_due = (float) $billLines->sum('due_amount');

        $customer_statement_report = $this->customerStatementReportSettings((int) $contact->business_id);
        $reports_footer = \App\System::where('key', 'admin_reports_footer')->first();

        return view('customers::customer_statement.print-list-customer-state')->with(compact(
            'row',
            'visibleCols',
            'logo',
            'business_details',
            'location_details',
            'contact',
            'for_pdf',
            'reprint',
            'billLines',
            'reprint_no',
            'total_balance_due',
            'customer_statement_report',
            'reports_footer'
        ));
    } catch (\Throwable $e) {
        if (request()->ajax()) {
            return response('Error: ' . $e->getMessage(), 500);
        }
        throw $e;
    }
}

	
	public function exportExcelListCustomerStatement($id)
{
    $customerStatement = CustomerStatement::with([
            'contact','user','location','details.transaction',
        ])
        ->leftJoin('vat_customer_statements', 'vat_customer_statements.id', 'customer_statements.linked_vat_statement')
        ->leftJoin('users as u', 'u.id', 'customer_statements.converted_by')
        ->select(
            'customer_statements.*',
            'u.username as user_converted',
            'vat_customer_statements.statement_no as vat_statement',
            'vat_customer_statements.print_date as vat_date'
        )
        ->where('customer_statements.id', $id)
        ->first();

    // عداد إعادة الطباعة
    $reprint_no = (int) $customerStatement->reprint_no;
    $customerStatement->reprint_no = $reprint_no + 1;
    $customerStatement->save();

    // هيدر التقرير
    $row = [];
    $row['date_printed'] = request()->get('print_date');
    $row['date_from']    = request()->get('date_from');
    $row['date_to']      = request()->get('date_to');
    $row['customer']     = optional($customerStatement->contact)->name ?? null;

    $html = '';
    if ((int)$customerStatement->is_converted === 1) {
        $html  = "<span class='badge bg-success'>" . __('contact.is_converted') . "</span><br>";
        $html .= "<span>" . __('contact.created_statement') . " <b>{$customerStatement->vat_statement}</b> "
               . __('contact.on') . " <b>" . $this->commonUtil->format_date($customerStatement->vat_date) . "</b></span><br>";
        $html .= "<span>" . e($customerStatement->user_converted) . "</span><br>";
    }
    $row['description'] = $html;
    $row['added_by']    = optional($customerStatement->user)->username ?? '';

    $sum = (float) $customerStatement->details->sum('invoice_amount');
    $row['statement_no']     = $customerStatement->statement_no;
    $row['statement_amount'] =
        '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $sum . '">'
        . $this->commonUtil->num_f($sum) . '</span>';

    // حالة الدفع
    $transactions = $customerStatement->details->pluck('transaction')->filter();
    $total  = $transactions->count();
    $dueCnt = $transactions->filter(fn($t) => in_array($t->payment_status, ['due','partial'], true))->count();
    if ($total === 0 || $dueCnt === 0) {
        $row['payment_status'] = "<span class='badge bg-success'>" . __('vat::lang.paid') . "</span>";
    } elseif ($dueCnt === $total) {
        $row['payment_status'] = "<span class='badge bg-danger'>" . __('vat::lang.due') . "</span>";
    } else {
        $row['payment_status'] = "<span class='badge bg-warning'>" . __('vat::lang.partial') . "</span>";
    }

    // ===== تحويل الفهارس لأسماء الأعمدة =====
    $visibleColsRaw = (string) request()->get('columns', '');
    $visibleIdx = array_values(array_filter(array_map('intval', explode(',', $visibleColsRaw))));

    // خريطة أعمدة تفاصيل البيان (مطابقة لما استعملناه في الـ View/Print)
    $detailIndexMap = [
        1 => 'date',
        2 => 'location',
        3 => 'invoice_no',
        4 => 'route_name',
        5 => 'vehicle_no',
        6 => 'customer_reference',
        7 => 'customer_po_no',
        8 => 'voucher_order_date',
        9 => 'product',
        10 => 'qty',
        11 => 'unit_price',
        12 => 'invoice_amount',
        13 => 'due_amount',
    ];

    $detailVisibleKeys = [];
    foreach ($visibleIdx as $i) {
        if (isset($detailIndexMap[$i])) {
            $detailVisibleKeys[] = $detailIndexMap[$i];
        }
    }
    // إن لم تُمرّر أعمدة، اعتبر الكل ظاهر
    if (empty($detailVisibleKeys)) {
        $detailVisibleKeys = array_values($detailIndexMap);
    }

    // ===== تجهيز بيانات السطور (مثل showCustomerStatement/rePrint) =====
    $contact          = Contact::findOrFail($customerStatement->customer_id);
    $logo             = CustomerStatementLogo::find($customerStatement->logo);
    $business_details = $this->businessUtil->getDetails($contact->business_id);
    $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();
    $for_pdf          = 1;

    // تاريخ من/إلى (من الطلب أو من السجل)
    $fromReq = request()->get('date_from');
    $toReq   = request()->get('date_to');

    $start = Carbon::parse($fromReq ?: ($customerStatement->date_from ?? now()->startOfYear()))->startOfDay();
    $end   = Carbon::parse($toReq   ?: ($customerStatement->date_to   ?? now()))->endOfDay();

    $vehFromRef = $this->getLatestVehicleFromReferences(
        $customerStatement->customer_id,
        $business_details->id ?? null
    ) ?? '';

    $rawLines = DB::table('customer_statement_details')
        ->where('statement_id', $id)
        ->whereBetween(
            DB::raw("DATE(COALESCE(order_date, date))"),
            [$start->toDateString(), $end->toDateString()]
        )
        ->selectRaw("
            DATE_FORMAT(COALESCE(date, order_date), '%Y-%m-%d') as date,
            location,
            invoice_no,
            route_name,
            TRIM(COALESCE(vehicle_number, '')) as vehicle_no,
            customer_reference,
            order_no as customer_po_no,
            DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d') as voucher_order_date,
            product,
            unit_price,
            SUM(qty) as qty,
            SUM(invoice_amount) as invoice_amount,
            SUM(due_amount) as due_amount
        ")
        ->groupBy([
            DB::raw("DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d')"),
            'location',
            'invoice_no',
            'route_name',
            'vehicle_no',
            'customer_reference',
            'customer_po_no',
            DB::raw("DATE_FORMAT(COALESCE(order_date, date), '%Y-%m-%d')"),
            'product',
            'unit_price',
        ])
        ->orderBy('date','asc')
        ->get();

    // fallback بسيط
    $sanitizeScalar = function ($v, int $max = 120) {
        if ($v === null || $v === '') return '';
        $decoded = json_decode($v, true);
        $val = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            ? implode(' + ', array_map(fn($x) => (string)$x, $decoded))
            : (string) $v;
        $val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val);
        if (mb_strlen($val) > $max) $val = mb_substr($val, 0, $max) . '…';
        return $val;
    };

    $billLines = collect($rawLines)->map(function ($r) use ($sanitizeScalar, $vehFromRef) {
        $veh = trim((string)($r->vehicle_no ?? ''));
        if ($veh === '') {
            $veh = $vehFromRef !== '' ? $vehFromRef : 'N/A';
        }

        return [
            'date'               => (string) ($r->date ?? ''),
            'location'           => (string) ($r->location ?? ''),
            'invoice_no'         => (string) ($r->invoice_no ?? ''),
            'route_name'         => (string) ($r->route_name ?? ''),
            'vehicle_no'         => $veh,
            'customer_reference' => (string) ($r->customer_reference ?? ''),
            'customer_po_no'     => (string) ($r->customer_po_no ?? ''),
            'voucher_order_date' => (string) ($r->voucher_order_date ?? ''),
            'product'            => $sanitizeScalar($r->product),
            'qty'                => (string) ($r->qty ?? ''),
            'unit_price'         => (string) ($r->unit_price ?? ''),
            'invoice_amount'     => (float)  ($r->invoice_amount ?? 0),
            'due_amount'         => (float)  ($r->due_amount ?? 0),
        ];
    })->values();

    // تنزيل الإكسل (FromView)
    return \Maatwebsite\Excel\Facades\Excel::download(
        new \App\Exports\ListCustomerStatement(
            $logo,
            $contact,
            $business_details,
            $for_pdf,
            $location_details,
            $reprint_no,
            $row,
            $detailVisibleKeys,   // ← أسماء الأعمدة الظاهرة
            $billLines            // ← البيانات
        ),
        "CustomerStatement.xls"
    );
}



    /**
     * Customers module owned font-setting endpoint for Customer Statement pages.
     * This mirrors the Contact module behaviour but stays inside the Customers
     * route namespace so AJAX/forms opened from /customers/* do not post to the
     * old Contact URL.
     */
    public function updateCustomerStatementFont(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id') ?? $request->session()->get('business.id');
            $data = $request->except('_token');

            $config = ReportConfiguration::where('business_id', $business_id)
                ->where('name', 'customer_statement_report')
                ->first();

            $existing_configs = !empty($config) ? json_decode($config->configurations, true) : [];
            $merged = array_merge(is_array($existing_configs) ? $existing_configs : [], $data);

            ReportConfiguration::updateOrCreate(
                ['business_id' => $business_id, 'name' => 'customer_statement_report'],
                ['configurations' => json_encode($merged)]
            );

            $output = [
                'success' => true,
                'msg' => __('business.settings_updated_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

}
