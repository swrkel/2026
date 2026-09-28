<?php
namespace Modules\Vat\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use App\ContactLedger;
// Separation step 4 (document 5-18): the shared `customer_references` table
// is reached through a VAT-owned model, so this file no longer depends on the
// core App\CustomerReference class when the Contact module is retired.
use Modules\Vat\Entities\SharedCustomerReference as CustomerReference;
use App\CustomerStatement;
use App\Exports\CustomerStatement as CustomerStatementExport;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel as MatExcel;
use Modules\Vat\Entities\RouteProduct;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\CustomerStatementFontSetting;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use Modules\Vat\Entities\VatStatementLogo;
use Modules\Vat\Entities\VatStatementPrefix;
use Mpdf\Mpdf;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class CustomerStatementController extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;


    /**
     * MA-002 PERF: request-scoped cache of route products.
     *
     * The "product" column runs once per row and, for route_operation rows,
     * loops the product ids stored as JSON and issues
     *     self::ma002RouteProduct($one)
     * for EVERY id. The same products recur across rows of a statement, so
     * the same records were re-fetched continuously.
     *
     * Route products are reference data and cannot change while the table
     * renders. Misses are cached too, so a missing product is not re-queried
     * on every row.
     */
    private static array $ma002RouteProductCache = [];

    private static function ma002RouteProduct($id)
    {
        if (empty($id)) {
            return null;
        }

        if (! array_key_exists($id, self::$ma002RouteProductCache)) {
            self::$ma002RouteProductCache[$id] = RouteProduct::where('id', $id)->first();
        }

        return self::$ma002RouteProductCache[$id];
    }

    /**
     * Utils
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $contactUtil;

    public function __construct(
        BusinessUtil $businessUtil,
        Util $commonUtil,
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        ContactUtil $contactUtil
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil     = $productUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->commonUtil      = $commonUtil;
        $this->businessUtil    = $businessUtil;
        $this->contactUtil     = $contactUtil;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);
        $default_start = new Carbon('first day of this month');
        $default_end   = new Carbon('last day of this month');

        $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
        $end_date   = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

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

        if ($request->ajax()) {
            $query = $this->__getStatement($business_id);

            // Apply date filtering when dates are provided
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            if (!empty($request->input('reference'))) {
                $reference_id = $request->input('reference');
                $customer_ref = CustomerReference::find($reference_id);
                $query->where('transactions.customer_ref', $customer_ref->reference);
            }

            $query->whereNotNull('transactions.contact_id');

            if (!empty($request->input('customer_id'))) {
                $cid = $request->input('customer_id');
                $query->where('transactions.contact_id', $cid);
            }

            // Apply customer type filtering
            if (!empty($request->input('customer_type')) && $request->input('customer_type') != 'all') {
                $customer_type = $request->input('customer_type');
                $query->join('contacts as contact_filter', 'transactions.contact_id', '=', 'contact_filter.id')
                      ->where('contact_filter.type', $customer_type);
            }

            // Apply search filtering
            if (!empty($request->input('search_term'))) {
                $search_term = $request->input('search_term');
                $query->where(function($q) use ($search_term) {
                    $q->where('transactions.invoice_no', 'like', '%' . $search_term . '%')
                      ->orWhere('cut.name', 'like', '%' . $search_term . '%')
                      ->orWhere('transactions.ref_no', 'like', '%' . $search_term . '%');
                });
            }

            $query->orderBy('transactions.transaction_date', 'asc');

            $products = $query->get();

            $datatable = DataTables::of($products)
                ->editColumn('product', function ($row) {
                    // route_operation: تجميع من RouteProduct كما كان
                    if ($row->tran_type == 'route_operation') {
                        $amounts = "";
                        if (!empty($row->product_id)) {
                            $prod_array = json_decode($row->product_id);
                            if (is_array($prod_array)) {
                                foreach ($prod_array as $key => $one) {
                                    $product = self::ma002RouteProduct($one);
                                    if (!empty($product)) {
                                        $amounts .= $product->name;
                                        if ($key != sizeof($prod_array) - 1) {
                                            $amounts .= " + ";
                                        }
                                    }
                                }
                            } else {
                                $product = self::ma002RouteProduct($prod_array);
                                if (!empty($product)) {
                                    $amounts .= $product->name;
                                }
                            }
                        }
                        return $amounts;
                    }

                    // للحالات الأخرى: لو أكثر من منتج نعرض أول اسم + إشارة
                    $ids = $row->tsl_product_ids ?? $row->scp_product_ids;
                    if (!empty($ids) && str_contains($ids, ',')) {
                        // عندنا أول اسم منتج من COALESCE(p_tsl.name, p_scp.name) في selectRaw
                        return !empty($row->product) ? ($row->product . ' + …') : __('lang_v1.multiple_items');
                    }

                    // عنصر واحد: نستخدم الاسم القادم من الـ join، وإلا نعرض الـ ID
                    if (!empty($row->product)) {
                        return $row->product;
                    }
                    if (!empty($ids)) {
                        return '#' . $ids;
                    }
                    return '';
                })
                ->removeColumn('enable_stock')
                ->removeColumn('unit')
                ->removeColumn('id')
                ->addColumn('quantity', function ($row) {
                    if ($row->tran_type == 'sell' || $row->tran_type == 'route_operation') {
                        $amounts = "";
                        if (!empty($row->sold_qty)) {
                            $qty_array = json_decode($row->sold_qty);
                            if (is_array($qty_array)) {
                                foreach ($qty_array as $key => $one) {
                                    $amounts .= number_format((float) $one, 2, '.', ',');
                                    if ($key != sizeof($qty_array) - 1) {
                                        $amounts .= " + ";
                                    }
                                }
                            } else {
                                $amounts .= number_format((float) $qty_array, 2, '.', ',');
                            }
                        }
                        return $amounts;
                    }
                    return '';
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->customer_name;
                })
                ->addColumn('action', function ($row) use ($edit_customer_statement) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $html .= '<li><a href="#" data-href="' . action("SellController@show", [$row->transaction_id]) . '" class="vat-ajax-modal-trigger" data-container=".customer_statement_modal"><i class="fa fa-external-link" aria-hidden="true"></i> ' . __("messages.view") . '</a></li>';

                    if ($edit_customer_statement) {
                        if (Gate::allows('edit_customer_statement')) {
                            $html .= '<li><a href="#" data-href="' . action("CustomerStatementController@edit", [$row->transaction_id]) . '" class="vat-ajax-modal-trigger" data-container=".customer_statement_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __("messages.edit") . '</a></li>';
                        }
                    }

                    $html .= '<li>
                        <a href="' . url('vat-module/customer-statement/delete-transaction/' . $row->transaction_id) . '"
                           data-id="' . $row->transaction_id . '"
                           class="customer-statement-delete">
                            <i class="fa fa-trash" aria-hidden="true"></i> ' . __("messages.delete") . '
                        </a>
                    </li>';

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('order_no', function ($row) {
                    return !empty($row->order_no) ? $row->order_no : $row->order_number;
                })
                ->editColumn('invoice_no', function ($row) {
                    $html = $row->invoice_no;
                    if ($row->sub_type == 'customer_loan') {
                        $html .= "<br><b>(" . __('petro::lang.customer_loans') . ")</b>";
                    }
                    if ($row->tran_type == 'direct_customer_loan') {
                        $html .= "<br><b>(" . __('lang_v1.direct_loan_to_customer') . ")</b>";
                    }
                    return $html;
                })
                ->editColumn('route_name', function ($row) {
                    return !empty($row->route_name) ? $row->route_name : '';
                })
                ->editColumn('vehicle_number', function ($row) {
                    return !empty($row->vehicle_number) ? $row->vehicle_number : '';
                })
                ->editColumn('reference', function ($row) {
                    return !empty($row->ref_no) ? $row->ref_no : '';
                })
                ->editColumn('order_date', function ($row) {
                    return !empty($row->order_date) ? $this->vatFormatter()->format_date($row->order_date) : '';
                })
                ->editColumn('unit_price', function ($row) {
                    $unit_price = isset($row->p_unit_price) ? (float) $row->p_unit_price : 0;
                    return number_format($unit_price, 2, '.', ',');
                })
                ->editColumn('final_total', function ($row) {
                    $amount = isset($row->total_amount) ? (float) $row->total_amount : 0;
                    return number_format($amount, 2, '.', ',');
                })
                ->addColumn('due_amount', function ($row) {
                    $amount = isset($row->total_amount) ? (float) $row->total_amount : 0;
                    $paid   = isset($row->total_paid) ? (float) $row->total_paid : 0;
                    $due    = $amount - $paid;

                    // data-orig-value يجب أن تكون قيمة خام بلا فورمات
                    return '<span class="display_currency due" data-currency_symbol="true" data-orig-value="' .
                        $due . '">' . number_format($due, 2, '.', ',') . '</span>';
                })
                ->editColumn('qty', function ($row) {
                    return !empty($row->qty) ? $this->vatFormatter()->num_uf($row->qty) : '';
                })
                ->editColumn('final_due_amount', '{{$final_total - $total_paid}}');

            $raw_columns = ['action', 'final_total', 'due_amount', 'invoice_no'];
            return $datatable->rawColumns($raw_columns)->make(true);
        }

        $customers         = Contact::where('business_id', $business_id)->whereIn('type', ['customer', 'both'])->pluck('name', 'id');
        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        
        // Customer types for filtering
        $customer_types = [
            'all' => __('lang_v1.all'),
            'customer' => __('lang_v1.customer'),
            'both' => __('lang_v1.both_supplier_customer'),
        ];
        
        $statement_no      = $this->__getStatementNo($business_id);
        $logos             = VatStatementLogo::where('business_id', $business_id)->select('*')->pluck('image_name', 'id');

        // حماية من null على السيرفر
        $raw               = CustomerStatementFontSetting::where('business_id', $business_id)->first()?->settings ?? json_encode([]);
        $invoice2_settings = (object) json_decode($raw);

        $reference             = CustomerReference::where('business_id', $business_id)->pluck('reference', 'id');
        $enable_126_statement  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_126_statement');

        return view('vat::customer_statement.index')->with(compact(
            'customers',
            'business_locations',
            'customer_types',
            'reference',
            'statement_no',
            'logos',
            'invoice2_settings',
            'enable_126_statement'
        ));
    }

    public function deleteTransuction($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        $output = [
            'success' => true,
            'msg'     => __("lang_v1.success"),
        ];

        return $output;
    }

    public function updateSetting(Request $request)
    {
        try {
            $business_id = request()->session()->get('user.business_id')
                ?: request()->session()->get('business.id')
                ?: optional(auth()->user())->business_id;
            $data        = request()->except('_token');
            DB::beginTransaction();

            $tdata = ['business_id' => $business_id, 'settings' => json_encode($data)];
            CustomerStatementFontSetting::updateOrCreate(['business_id' => $business_id], $tdata);

            DB::commit();
            $output = [
                'success' => true,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->to(url()->previous() . '#font-setting')->with('status', $output);
    }

    public function updatePrefixes()
    {
        $business_id = $this->resolveBusinessId(request());
        $prefixes = $this->getLatestStatementPrefix($business_id);

        if (empty($prefixes)) {
            return response('done');
        }

        $prefix = (string) ($prefixes->prefix ?? '');
        $starting_no = (string) $prefixes->starting_no;
        $pad_length = max(strlen($starting_no), 1);

        $query = VatCustomerStatement::where('business_id', $business_id);
        $this->applyStatementPrefixFilter($query, $prefix);

        $statements = $query->orderBy('id', 'ASC')->get();

        foreach ($statements as $statement) {
            $sequence = $this->extractStatementSequence($statement->statement_no, $prefix);

            if ($sequence === null) {
                continue;
            }

            // Preserve the existing sequence and only remove the legacy injected separator.
            $statement->statement_no = $prefix . str_pad((string) $sequence, $pad_length, '0', STR_PAD_LEFT);
            $statement->save();
        }

        return response('done');
    }

    /**
     * الاستعلام الموحد
     */
    public function __getStatement($business_id)
    {
        // بيع مفصّل
        $tslAgg = DB::table('transaction_sell_lines')
            ->selectRaw(
                'transaction_id,
                 SUM(quantity) as total_qty,
                 AVG(unit_price) as unit_price,
                 SUM(unit_price * quantity) as total_amount,
                 GROUP_CONCAT(DISTINCT product_id) as product_ids,
                 SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT product_id), ",", 1) as first_product_id'
            )
            ->groupBy('transaction_id');

        // تسوية ديون بيع
        $settlementAgg = DB::table('settlement_credit_sale_payments')
            ->selectRaw(
                'id as credit_sale_id,
                 SUM(qty) as total_qty,
                 AVG(price) as unit_price,
                 SUM(price * qty) as total_amount,
                 GROUP_CONCAT(DISTINCT product_id) as product_ids,
                 SUBSTRING_INDEX(GROUP_CONCAT(DISTINCT product_id), ",", 1) as first_product_id'
            )
            ->groupBy('id');

        $query = DB::table('transactions')
            ->leftJoinSub($tslAgg, 'tsl', 'transactions.id', '=', 'tsl.transaction_id')
            ->leftJoinSub($settlementAgg, 'scp', 'transactions.credit_sale_id', '=', 'scp.credit_sale_id')
            // Direct join to get customer_reference (vehicle number for credit-sale transactions)
            ->leftJoin('settlement_credit_sale_payments as scpRaw', 'transactions.credit_sale_id', '=', 'scpRaw.id')
            ->leftJoin('route_operations', 'transactions.id', '=', 'route_operations.transaction_id')
            ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')

            // ربط أسماء المنتجات بشكل آمن عبر أول منتج
            ->leftJoin('products as p_tsl', 'p_tsl.id', '=', 'tsl.first_product_id')
            ->leftJoin('products as p_scp', 'p_scp.id', '=', 'scp.first_product_id')

            ->leftJoin('fleets as f', 'route_operations.fleet_id', '=', 'f.id')

            ->selectRaw('
                COALESCE(p_tsl.name, p_scp.name) as product,
                COALESCE(tsl.unit_price, scp.unit_price) as p_unit_price,
                COALESCE(tsl.total_amount, scp.total_amount) as total_amount,
                COALESCE(tsl.product_ids, scp.product_ids) as product_id,
                COALESCE(tsl.total_qty, scp.total_qty, route_operations.qty) as sold_qty,

                transactions.transaction_date as transaction_date,
                transactions.type as tran_type,
                transactions.ref_no,
                transactions.invoice_no,
                transactions.customer_ref,
                transactions.order_no,
                transactions.order_date,
                transactions.contact_id,
                transactions.sub_type,
                COALESCE(
                    (SELECT GROUP_CONCAT(COALESCE(fl.vehicle_number, ro.order_number) SEPARATOR \', \') 
                     FROM route_operations ro 
                     LEFT JOIN fleets fl ON ro.fleet_id = fl.id 
                     WHERE ro.transaction_id = transactions.id),
                    scpRaw.customer_reference,
                    transactions.customer_ref
                ) as vehicle_number,
                scpRaw.customer_reference,
                transactions.final_total,
                transactions.id as transaction_id,
                route_operations.order_number,
                tsl.transaction_id as tsl_id,
                scp.credit_sale_id as scp_id,
                cut.name as customer_name,
                cRef.reference,

                tsl.product_ids as tsl_product_ids,
                scp.product_ids as scp_product_ids,

                (SELECT SUM(IF(TP.is_return = 1, -1*TP.amount, TP.amount))
                 FROM transaction_payments AS TP
                 WHERE TP.transaction_id = transactions.id) as total_paid
            ')
            ->leftJoin('contacts as cut', 'transactions.contact_id', '=', 'cut.id')
            ->leftJoin('customer_references as cRef', 'transactions.customer_ref', '=', 'cRef.reference')
            ->where(function ($q) use ($business_id) {
                $q->where('transactions.business_id', $business_id);
            })
            ->whereIn('transactions.type', ['property_sell', 'sell'])
            ->whereIn('transactions.payment_status', ['due', 'partial'])
            ->whereNull('transactions.deleted_at')
            ->groupBy('transactions.id');

        return $query;
    }

    public function create()
    {
        //
    }

    public function downloadPdf(Request $request)
    {
        $html = $request->get('html');
        // Doc 7942: Customer statement output must not be truncated on A4.
        // Statement tables are wide, so use A4 landscape like the HTML print layout.
        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->shrink_tables_to_fit = 1;
        $mpdf->SetFont('Calibri', '', 12);
        $mpdf->WriteHTML($html);

        $directoryPath = config('constants.reports_directory');
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        $filename = Str::random(40) . ".pdf";
        $filePath = config('constants.reports_directory') . $filename;
        $mpdf->Output($filePath, 'F');

        return response()->json(['path' => url("reports/" . $filename)]);
    }

    public function store(Request $request)
    {
        $business_id = $this->resolveBusinessId($request, $request->input('customer_id'));

        try {
            $default_start = new Carbon('first day of this month');
            $default_end   = new Carbon('last day of this month');

            $start_date = !empty($request->get('start_date')) ? date('Y-m-d', strtotime($request->get('start_date'))) : $default_start->format('Y-m-d');
            $end_date   = !empty($request->get('end_date')) ? date('Y-m-d', strtotime($request->get('end_date'))) : $default_end->format('Y-m-d');

            $query = $this->__getStatement($business_id);

            // Apply date filtering when dates are provided
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $query->whereNotNull('transactions.contact_id');
            if (!empty($request->input('customer_id'))) {
                $cid = $request->input('customer_id');
                $query->where('transactions.contact_id', $cid);
            }

            $transactions = $query->get();

            DB::beginTransaction();

            $contact = Contact::where('business_id', $business_id)
                ->findOrFail($request->customer_id);
            $statement_no = $this->__getStatementNo($business_id);

            $statement = VatCustomerStatement::create([
                'business_id'      => $business_id,
                'logo'             => $request->logo,
                'customer_id'      => $request->customer_id,
                'statement_no'     => $statement_no,
                'print_date'       => date('Y-m-d'),
                'date_from'        => Carbon::parse($start_date)->format('Y-m-d'),
                'date_to'          => Carbon::parse($end_date)->format('Y-m-d'),
                'added_by'         => Auth::user()->id,
                'price_adjustment' => request()->price_adjustment,
            ]);

            $pa_transaction = $this->transactionUtil->createOrUpdatePriceAdjustment($statement, $statement_no);

            foreach ($transactions as $transaction) {
                $invoice_no = $transaction->invoice_no;

                VatCustomerStatementDetail::create([
                    'business_id'            => $business_id,
                    'statement_id'           => $statement->id,
                    'date'                   => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                    'invoice_no'             => $invoice_no,
                    'order_no'               => $transaction->order_no,
                    'vehicle_number'         => !empty($transaction->vehicle_number) ? $transaction->vehicle_number : "",
                    'product'                => $transaction->product,
                    'unit_price'             => !empty($transaction->p_unit_price) ? $transaction->p_unit_price : 1,
                    'qty'                    => $transaction->sold_qty,
                    'invoice_amount'         => $transaction->final_total,
                    'product_id'             => $transaction->product_id,
                    'transaction_id'         => $transaction->transaction_id,
                    'unit_price_before_tax'  => $transaction->unit_price_before_tax ?? 0,
                ]);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('messages.success'),
                'statement_no' => $statement_no,
                'date_from' => $start_date,
                'date_to' => $end_date,
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function convertVAT(Request $request, $id)
    {
        try {
            $default_start = new Carbon('first day of this month');
            $default_end   = new Carbon('last day of this month');

            $customer_statement = CustomerStatement::findOrFail($id);

            $business_id = $customer_statement->business_id;

            $logo = VatStatementLogo::where('business_id', $business_id)->first()->id ?? null;

            $start_date = !empty($customer_statement->date_from) ? $customer_statement->date_from : $default_start->format('Y-m-d');
            $end_date   = !empty($customer_statement->date_to) ? $customer_statement->date_to : $default_end->format('Y-m-d');

            $query = $this->__getStatement($business_id);

            // Apply date filtering when dates are provided
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $query->whereNotNull('transactions.contact_id');
            if (!empty($customer_statement->customer_id)) {
                $cid = $customer_statement->customer_id;
                $query->where('transactions.contact_id', $cid);
            }

            $transactions = $query->get();

            DB::beginTransaction();

            $contact      = Contact::findOrFail($customer_statement->customer_id);
            $statement_no = $this->__getStatementNo($business_id);

            $statement = VatCustomerStatement::create([
                'business_id'        => $business_id,
                'logo'               => $logo,
                'customer_id'        => $customer_statement->customer_id,
                'statement_no'       => $statement_no,
                'print_date'         => date('Y-m-d'),
                'date_from'          => $start_date,
                'date_to'            => $end_date,
                'added_by'           => Auth::user()->id,
                'price_adjustment'   => 0,
                'is_converted'       => 1,
                'converted_by'       => auth()->user()->id,
                'linked_vat_statement'=> $customer_statement->id,
            ]);

            foreach ($transactions as $transaction) {
                $invoice_no = $transaction->invoice_no;

                VatCustomerStatementDetail::create([
                    'business_id'            => $business_id,
                    'statement_id'           => $statement->id,
                    'date'                   => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                    'invoice_no'             => $invoice_no,
                    'order_no'               => $transaction->order_no,
                    'vehicle_number'         => !empty($transaction->vehicle_number) ? $transaction->vehicle_number : "",
                    'product'                => $transaction->product,
                    'unit_price'             => !empty($transaction->p_unit_price) ? $transaction->p_unit_price : 1,
                    'qty'                    => $transaction->sold_qty,
                    'invoice_amount'         => $transaction->final_total,
                    'product_id'             => $transaction->product_id,
                    'transaction_id'         => $transaction->transaction_id,
                    'unit_price_before_tax'  => $transaction->unit_price_before_tax ?? null,
                ]);
            }

            $customer_statement->is_converted       = 1;
            $customer_statement->converted_by       = auth()->user()->id;
            $customer_statement->linked_vat_statement = $statement->id;
            $customer_statement->save();

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
     * Show
     */
    public function show($id)
    {
        $business_id = $this->resolveBusinessId(request());
        $statement  = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $reprint_no = $statement->reprint_no;
        $statement->reprint_no = $reprint_no + 1;
        $statement->save();
        $statement->statement_no = $this->normalizeStatementNoForDisplay($statement->statement_no, $statement->business_id);

        $contact     = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);
        $start_date  = $statement->date_from;
        $end_date    = $statement->date_to;

        $statement_details = VatCustomerStatementDetail::leftjoin('transactions', 'transactions.id', 'vat_customer_statement_details.transaction_id')
            ->where('vat_customer_statement_details.business_id', $business_id)
            ->where('statement_id', $id)
            ->select(
                'vat_customer_statement_details.*', 
                'transactions.transaction_date as tdate',
                DB::raw("COALESCE(
                    NULLIF(vat_customer_statement_details.vehicle_number, ''),
                    (SELECT GROUP_CONCAT(COALESCE(fl.vehicle_number, ro.order_number) SEPARATOR ', ') FROM route_operations ro LEFT JOIN fleets fl ON ro.fleet_id = fl.id WHERE ro.transaction_id = transactions.id),
                    (SELECT customer_reference FROM settlement_credit_sale_payments WHERE id = transactions.credit_sale_id LIMIT 1)
                ) as vehicle_number")
            )
            ->get();

        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $business_details = $this->businessUtil->getDetails($business_id);

        $logo      = VatStatementLogo::where('business_id', $business_id)->find($statement->logo);
        $reference = CustomerReference::where('business_id', $business_id)->pluck('reference', 'id');

        $total_balance_due = $this->__calculateBalanceDue($statement_details);

        return view('vat::customer_statement.show')->with(compact(
            'logo',
            'reference',
            'contact',
            'business_details',
            'location_details',
            'statement_details',
            'statement',
            'start_date',
            'end_date',
            'reprint_no',
            'id',
            'total_balance_due'
        ));
    }

    public function edit($id)
    {
        $business_id = $this->resolveBusinessId(request());
        $statement = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);

        if ($this->getVatStatementPaidAmount($statement->id) > 0) {
            return response('A paid VAT Statement cannot be edited. Delete its payment first.', 409);
        }

        return view('vat::customer_statement.edit')->with(compact('statement'));
    }

    public function update(Request $request, $id)
    {
        $business_id = $this->resolveBusinessId($request);

        $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        try {
            $statement = VatCustomerStatement::where('business_id', $business_id)
                ->findOrFail($id);

            if ($this->getVatStatementPaidAmount($statement->id) > 0) {
                $output = [
                    'success' => 0,
                    'msg' => 'A paid VAT Statement cannot be edited. Delete its payment first.',
                ];

                return $request->ajax()
                    ? response()->json($output, 409)
                    : Redirect::back()->with('status', $output);
            }

            $data = [
                'date_from' => $request->date_from,
                'date_to'   => $request->date_to,
            ];

            DB::beginTransaction();

            $statement->update($data);
            VatCustomerStatementDetail::where('business_id', $business_id)
                ->where('statement_id', $id)
                ->forceDelete();

            $start_date = $request->date_from;
            $end_date   = $request->date_to;
            $cid        = $statement->customer_id;

            $query = $this->__getStatement($business_id);

            // Apply date filtering when dates are provided
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date)
                      ->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $query->whereNotNull('transactions.contact_id');
            if (!empty($cid)) {
                $query->where('transactions.contact_id', $cid);
            }

            $transactions = $query->get();

            $contact = Contact::findOrFail($cid);

            foreach ($transactions as $transaction) {
                $invoice_no = $transaction->invoice_no;

                VatCustomerStatementDetail::create([
                    'business_id'            => $business_id,
                    'statement_id'           => $statement->id,
                    'date'                   => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                    'invoice_no'             => $invoice_no,
                    'order_no'               => $transaction->order_no,
                    'vehicle_number'         => !empty($transaction->vehicle_number) ? $transaction->vehicle_number : "",
                    'product'                => $transaction->product,
                    'unit_price'             => !empty($transaction->p_unit_price) ? $transaction->p_unit_price : 1,
                    'qty'                    => $transaction->sold_qty,
                    'invoice_amount'         => $transaction->final_total,
                    'product_id'             => $transaction->product_id,
                    'transaction_id'         => $transaction->transaction_id,
                    'unit_price_before_tax'  => $transaction->unit_price_before_tax ?? null,
                ]);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('messages.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with('status', $output);
    }

    public function destroy($id)
    {
        if (request()->ajax()) {
            // Check VAT Module Main permission
            $business_id = $this->resolveBusinessId(request());
            if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_main_delete_customer_statement')
                || !Gate::allows('vat.delete_customer_statement')) {
                return [
                    'success' => false,
                    'msg' => __('messages.permission_denied'),
                ];
            }
            
            try {
                $customer_statement = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);

                if ($this->getVatStatementPaidAmount($customer_statement->id) > 0) {
                    return [
                        'success' => false,
                        'msg' => 'Delete the statement payment before deleting the VAT Statement.',
                    ];
                }

                $changed_msg = "VAT Customer Statement #" . $customer_statement->statement_no . " has been deleted by " . auth()->user()->username;

                $activity = new Activity();
                $activity->log_name   = "VAT Customer Statement";
                $activity->description= "delete";
                $activity->subject_id = $id;
                $activity->subject_type = "Modules\Vat\Entities\VatCustomerStatement";
                $activity->causer_id  = auth()->user()->id;
                $activity->causer_type= 'App\User';
                $activity->properties = $changed_msg;
                $activity->created_at = date('Y-m-d H:i');
                $activity->updated_at = date('Y-m-d H:i');
                $activity->save();

                $customer_statement->delete();
                VatCustomerStatementDetail::where('statement_id', $id)->delete();

                $output = [
                    'success' => true,
                    'msg'     => __("lang_v1.success"),
                ];
            } catch (\Exception $e) {
                Log::emergency('Customer statement delete failed', [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ]);
                $output = [
                    'success' => false,
                    'msg'     => __("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }

    public function destroyPayments($id)
    {
        if (request()->ajax()) {
            // Check VAT Module Main permission
            $business_id = $this->resolveBusinessId(request());
            if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_main_delete_statement_payments')
                || !Gate::allows('vat.delete_statement_payment')) {
                return [
                    'success' => false,
                    'msg' => __('messages.permission_denied'),
                ];
            }
            
            try {
                DB::beginTransaction();
                $customer_statement = VatCustomerStatement::where('business_id', $business_id)
                    ->findOrFail($id);

                $changed_msg = "Payments for VAT Customer Statement #" . $customer_statement->statement_no . " has been deleted by " . auth()->user()->username;

                $activity = new Activity();
                $activity->log_name   = "VAT Customer Statement";
                $activity->description= "delete";
                $activity->subject_id = $id;
                $activity->subject_type = "Modules\Vat\Entities\VatCustomerStatement";
                $activity->causer_id  = auth()->user()->id;
                $activity->causer_type= 'App\User';
                $activity->properties = $changed_msg;
                $activity->created_at = date('Y-m-d H:i');
                $activity->updated_at = date('Y-m-d H:i');
                $activity->save();

                $parent_payments = TransactionPayment::where('business_id', $business_id)
                    ->where('linked_vat_customer_statement', $customer_statement->id)
                    ->whereNull('parent_id')
                    ->get();

                $transaction_ids = [];

                foreach ($parent_payments as $parent_payment) {
                    $child_payments = TransactionPayment::where('business_id', $business_id)
                        ->where('parent_id', $parent_payment->id)
                        ->get();
                    foreach ($child_payments as $child_payment) {
                        if (!empty($child_payment->transaction_id)) {
                            $transaction_ids[] = (int) $child_payment->transaction_id;
                        }
                        $child_payment->deleted_by = auth()->id();
                        $child_payment->save();
                        $child_payment->delete();
                    }

                    AccountTransaction::where('business_id', $business_id)
                        ->where('transaction_payment_id', $parent_payment->id)
                        ->delete();
                    ContactLedger::where('business_id', $business_id)
                        ->where('transaction_payment_id', $parent_payment->id)
                        ->delete();

                    $parent_payment->deleted_by = auth()->id();
                    $parent_payment->save();
                    $parent_payment->delete();
                }

                foreach (array_unique($transaction_ids) as $transaction_id) {
                    $this->transactionUtil->updatePaymentStatus($transaction_id);
                }

                DB::commit();

                $output = [
                    'success' => true,
                    'msg'     => __("lang_v1.success"),
                ];
            } catch (\Exception $e) {
                Log::emergency('Customer statement payment delete failed', [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'message' => $e->getMessage(),
                ]);

                DB::rollback();

                $output = [
                    'success' => false,
                    'msg'     => __("messages.something_went_wrong"),
                ];
            }
            return $output;
        }
    }

    public function getMinimumDate(Request $r)
    {
        $business_id = $this->resolveBusinessId($r, $r->get('id'));
        $customer_id   = $r->get('id');
        $customer_date = VatCustomerStatement::where('business_id', $business_id)
            ->where('customer_id', $customer_id)
            ->orderBy('date_to', 'desc')
            ->first();

        return response()->json(['date' => $customer_date ? $customer_date->date_to : null]);
    }

    public function rePrint(Request $request, $id)
    {
        $business_id = $this->resolveBusinessId($request);
        $statement  = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $reprint_no = $statement->reprint_no;
        $statement->reprint_no = $reprint_no + 1;
        $statement->save();
        $statement->statement_no = $this->normalizeStatementNoForDisplay($statement->statement_no, $statement->business_id);

        $contact = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;
        $end_date   = $statement->date_to;

        $statement_details = VatCustomerStatementDetail::leftjoin('transactions', 'transactions.id', 'vat_customer_statement_details.transaction_id')
            ->where('vat_customer_statement_details.business_id', $business_id)
            ->where('statement_id', $id)
            ->select(
                'vat_customer_statement_details.*', 
                'transactions.transaction_date as tdate',
                DB::raw("COALESCE(
                    NULLIF(vat_customer_statement_details.vehicle_number, ''),
                    (SELECT GROUP_CONCAT(COALESCE(fl.vehicle_number, ro.order_number) SEPARATOR ', ') FROM route_operations ro LEFT JOIN fleets fl ON ro.fleet_id = fl.id WHERE ro.transaction_id = transactions.id),
                    (SELECT customer_reference FROM settlement_credit_sale_payments WHERE id = transactions.credit_sale_id LIMIT 1)
                ) as vehicle_number")
            )
            ->get();

        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $business_details = $this->businessUtil->getDetails($business_id);

        $total_balance_due = $this->__calculateBalanceDue($statement_details);
        $logo = VatStatementLogo::where('business_id', $business_id)->find($statement->logo);

        return view('vat::customer_statement.print')->with(compact(
            'logo',
            'contact',
            'business_details',
            'location_details',
            'statement_details',
            'total_balance_due',
            'statement',
            'start_date',
            'end_date',
            'reprint_no'
        ));
    }

    public function exportExcel($id)
    {
        $business_id = $this->resolveBusinessId(request());
        $statement  = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $reprint_no = $statement->reprint_no;
        $statement->reprint_no = $reprint_no + 1;
        $statement->save();
        $statement->statement_no = $this->normalizeStatementNoForDisplay($statement->statement_no, $statement->business_id);

        $contact     = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);
        $start_date  = $statement->date_from;
        $end_date    = $statement->date_to;

        $statement_details = VatCustomerStatementDetail::leftjoin('transactions', 'transactions.id', 'vat_customer_statement_details.transaction_id')
            ->where('vat_customer_statement_details.business_id', $business_id)
            ->where('statement_id', $id)
            ->select(
                'vat_customer_statement_details.*', 
                'transactions.transaction_date as tdate',
                DB::raw("COALESCE(
                    NULLIF(vat_customer_statement_details.vehicle_number, ''),
                    (SELECT GROUP_CONCAT(COALESCE(fl.vehicle_number, ro.order_number) SEPARATOR ', ') FROM route_operations ro LEFT JOIN fleets fl ON ro.fleet_id = fl.id WHERE ro.transaction_id = transactions.id),
                    (SELECT customer_reference FROM settlement_credit_sale_payments WHERE id = transactions.credit_sale_id LIMIT 1)
                ) as vehicle_number")
            )
            ->get();

        $total_balance_due = $this->__calculateBalanceDue($statement_details);

        $contact_id       = $contact->id;
        $ledger_details   = [];
        $ledger_details['opening_balance'] = 0;

        if ($contact->type == 'customer') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getCustomerBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getCustomerBalance($contact_id, $business_id);
        }

        if ($contact->type == 'supplier') {
            $ledger_details['beginning_balance'] = $this->contactUtil->getSupplierBf($contact_id, $business_id, $start_date);
            $ledger_details['balance_details']   = $this->contactUtil->getSupplierBalance($contact_id, $business_id);
        }

        $business_details  = $this->businessUtil->getDetails($business_id);
        $location_details  = BusinessLocation::where('business_id', $business_id)->first();

        $for_pdf  = 1;
        $reprint  = 1;
        $logo     = VatStatementLogo::where('business_id', $business_id)->find($statement->logo);

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

    public function getStatementHeader(Request $request, $statement_no)
    {
        $contact_id = $request->input('customer_id');
        $start_date = $this->normaliseDateInput($request->input('start_date'));
        $end_date = $this->normaliseDateInput($request->input('end_date'));
        $business_id = $this->resolveBusinessId($request, $contact_id);

        $contact = Contact::where('business_id', $business_id)
            ->findOrFail($contact_id);
        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $business_details = $this->businessUtil->getDetails($business_id);

        $for_pdf = 1;

        return view('vat::customer_statement.partials.print_statement_header')
            ->with(compact(
                'contact',
                'for_pdf',
                'location_details',
                'statement_no',
                'business_details',
                'start_date',
                'end_date'
            ))
            ->render();
    }

    public function getStatementFooter(Request $request, $statement_no)
    {
        $contact_id       = $request->customer_id;
        $start_date       = $request->start_date;
        $end_date         = $request->end_date;
        $price_adjustment = $request->price_adjustment ?? 0;

        $business_id = $this->resolveBusinessId($request, $contact_id);
        $contact = Contact::where('business_id', $business_id)
            ->findOrFail($contact_id);

        $query = $this->__getStatement($business_id);
        // Apply date filtering when dates are provided
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)
                  ->whereDate('transactions.transaction_date', '<=', $end_date);
        }
        $query->whereNotNull('transactions.contact_id');
        if (!empty($request->input('customer_id'))) {
            $cid = $request->input('customer_id');
            $query->where('transactions.contact_id', $cid);
        }

        $total = 0;
        $transactions = $query->get();

        foreach ($transactions as $row) {
            if ($row->tran_type == 'sell') {
                $total += ((float)$row->sold_qty * (float)$row->p_unit_price);
            } else {
                $total += ((float)$row->final_total);
            }
        }

        return view('vat::customer_statement.partials.print_statement_footer')
            ->with(compact('total', 'price_adjustment'))
            ->render();
    }

    public function getPriceAdjustment(Request $request, $statement_no)
    {
        $contact_id       = $request->customer_id;
        $start_date       = $request->start_date;
        $end_date         = $request->end_date;
        $price_adjustment = $request->price_adjustment ?? 0;

        $contact     = Contact::findOrFail($contact_id);
        $business_id = $contact->business_id;

        $query = $this->__getStatement($business_id);

        // Apply date filtering when dates are provided
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereDate('transactions.transaction_date', '>=', $start_date)
                  ->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        $query->whereNotNull('transactions.contact_id');

        if (!empty($request->input('customer_id'))) {
            $cid = $request->input('customer_id');
            $query->where('transactions.contact_id', $cid);
        }

        $total = 0;
        $transactions = $query->get();

        foreach ($transactions as $row) {
            if ($row->tran_type == 'sell') {
                $total += ((float)$row->sold_qty * (float)$row->p_unit_price);
            } else {
                $total += ((float)$row->final_total);
            }
        }

        $business_id = $this->resolveBusinessId($request, $contact_id);
        $tax_rate = \App\TaxRate::where('business_id', $business_id)->first()->amount ?? 0;
        $pre_tax  = $total / (1 + ($tax_rate / 100));
        $tax_total = ($tax_rate / 100) * $pre_tax;
        $grand_total = $tax_total + $pre_tax;

        $totalAmount       = $grand_total + $price_adjustment;
        $fTotalAmount      = round($totalAmount, 2);
        $adjustmentAmount  = $fTotalAmount - $totalAmount;

        return $adjustmentAmount;
    }

    public function __getStatementNo($business_id = null)
    {
        $business_id = $business_id ?: $this->resolveBusinessId(request());
        $prefixes = $this->getLatestStatementPrefix($business_id);

        if (empty($prefixes)) {
            return '';
        }

        $prefix = (string) ($prefixes->prefix ?? '');
        $starting_no_string = (string) $prefixes->starting_no;
        $starting_no_numeric = (int) $starting_no_string;

        $query = VatCustomerStatement::where('business_id', $business_id);
        $this->applyStatementPrefixFilter($query, $prefix);
        $existing = $query->orderByDesc('id')->first();

        $current_no = !empty($existing)
            ? $this->extractStatementSequence($existing->statement_no, $prefix)
            : null;

        $new_no = $current_no !== null && $current_no >= $starting_no_numeric
            ? $current_no + 1
            : $starting_no_numeric;

        $pad_length = max(strlen($starting_no_string), 1);
        $next_no_padded = str_pad((string) $new_no, $pad_length, '0', STR_PAD_LEFT);

        // The prefix already contains every character selected by the user.
        // Concatenate the sequence directly without adding an automatic hyphen.
        return $prefix . $next_no_padded;
    }

    /**
     * Get the latest statement prefix configured for the current business only.
     */
    private function getLatestStatementPrefix($business_id)
    {
        return VatStatementPrefix::where('business_id', $business_id)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Restrict statement numbers to the exact configured prefix.
     * The explicit ESCAPE character keeps '%' and '_' in a prefix literal.
     */
    private function applyStatementPrefixFilter($query, string $prefix): void
    {
        if ($prefix === '') {
            return;
        }

        $pattern = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $prefix) . '%';
        $query->whereRaw("statement_no LIKE ? ESCAPE '='", [$pattern]);
    }

    /**
     * Read the numeric sequence after the exact prefix.
     * A single leading hyphen is accepted only for legacy numbers generated
     * before this fix, so the next number continues correctly after deployment.
     */
    private function extractStatementSequence($statement_no, string $prefix): ?int
    {
        $statement_no = (string) $statement_no;

        if ($prefix !== '' && !Str::startsWith($statement_no, $prefix)) {
            return null;
        }

        $sequence = substr($statement_no, strlen($prefix));

        if (Str::startsWith($sequence, '-')) {
            $sequence = substr($sequence, 1);
        }

        return $sequence !== '' && ctype_digit($sequence)
            ? (int) $sequence
            : null;
    }

    /**
     * Hide the legacy separator on already-saved statements without changing
     * any character that belongs to the configured prefix itself.
     */
    private function normalizeStatementNoForDisplay($statement_no, $business_id): string
    {
        $statement_no = (string) $statement_no;
        $prefixes = $this->getLatestStatementPrefix($business_id);

        if (empty($prefixes)) {
            return $statement_no;
        }

        $prefix = (string) ($prefixes->prefix ?? '');
        $legacy_prefix = $prefix . '-';

        if (!Str::startsWith($statement_no, $legacy_prefix)) {
            return $statement_no;
        }

        $sequence = substr($statement_no, strlen($legacy_prefix));

        return $sequence !== '' && ctype_digit($sequence)
            ? $prefix . $sequence
            : $statement_no;
    }

    public function getCustomerStatementNo(Request $request)
    {
        $business_id = $this->resolveBusinessId($request, $request->input('customer_id'));
        $statement_no = $this->__getStatementNo($business_id);

        $header            = (string) $this->getStatementHeader($request, $statement_no);
        $footer            = (string) $this->getStatementFooter($request, $statement_no);
        $priceAdjustmentAmt= (string) $this->getPriceAdjustment($request, $statement_no);

        return [
            'statement_no'       => $statement_no,
            'header'             => $header,
            'footer'             => $footer,
            'priceAdjustmentAmt' => $priceAdjustmentAmt,
            'date_from' => $request->input('start_date'),
            'date_to' => $request->input('end_date'),
        ];
    }

    /**
     * Original amount saved in the VAT Statement. This is also the amount shown
     * in the List VAT Statements table and is never accepted from the browser.
     */
    private function getVatStatementAmount(VatCustomerStatement $statement): float
    {
        return round(
            (float) $statement->details()->sum('invoice_amount')
            + (float) ($statement->price_adjustment ?? 0),
            6
        );
    }

    /**
     * Only parent payments are counted. Child payments are allocations against
     * source invoices and must not be counted again.
     */
    private function getVatStatementPaidAmount(int $statementId): float
    {
        return round((float) TransactionPayment::where('linked_vat_customer_statement', $statementId)
            ->whereNull('parent_id')
            ->sum('amount'), 6);
    }

    private function isVatStatementPaid(VatCustomerStatement $statement): bool
    {
        $statementAmount = $this->getVatStatementAmount($statement);

        return $statementAmount > 0
            && $this->getVatStatementPaidAmount($statement->id) + 0.000001 >= $statementAmount;
    }

    /**
     * Keep the same access rule used by the application's standard customer
     * payment flow. The UI and both payment endpoints use this guard.
     */
    private function canPayVatStatement(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        /*
         * Keep the existing sales/purchase payment permissions, while also
         * allowing users who are expressly authorised to maintain VAT
         * Statements. This prevents the Pay Statement Amount action from being
         * hidden for VAT-only users who do not create normal sales or purchases.
         */
        return Gate::allows('purchase.create')
            || Gate::allows('sell.create')
            || Gate::allows('edit.vat_statement')
            || Gate::allows('edit_customer_statement')
            || Gate::allows('vat.delete_statement_payment');
    }

    /**
     * Show the system-standard payment form for a VAT Statement. The amount is
     * display-only; the POST endpoint always recalculates it from the database.
     */
    public function payStatementAmountForm(Request $request, $id)
    {
        abort_unless($request->ajax(), 404);
        abort_unless($this->canPayVatStatement(), 403, 'Unauthorized action.');

        $business_id = $this->resolveBusinessId($request);
        $statement = VatCustomerStatement::with('contact')
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $statement_amount = $this->getVatStatementAmount($statement);
        $statement_amount_display = $this->vatFormatter()->num_f($statement_amount);
        $paid_amount = $this->getVatStatementPaidAmount($statement->id);
        $is_paid = $statement_amount > 0 && $paid_amount + 0.000001 >= $statement_amount;
        $has_payment = $paid_amount > 0;
        $statement_display_no = $this->normalizeStatementNoForDisplay(
            $statement->statement_no,
            $business_id
        );

        $business_locations = BusinessLocation::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $business_location_id = (int) VatCustomerStatementDetail::join(
                'transactions',
                'transactions.id',
                '=',
                'vat_customer_statement_details.transaction_id'
            )
            ->where('vat_customer_statement_details.statement_id', $statement->id)
            ->value('transactions.location_id');

        if ($business_location_id <= 0) {
            $business_location_id = (int) $business_locations->keys()->first();
        }

        $payment_types = $business_location_id > 0
            ? $this->transactionUtil->payment_types($business_location_id)
            : [];
        unset($payment_types['credit_sale'], $payment_types['location_id']);

        $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
        $default_account_id = (int) Account::where('business_id', $business_id)
            ->where('name', 'Cash')
            ->where('is_closed', 0)
            ->value('id');
        $payment_line = new TransactionPayment();
        $payment_line->method = 'cash';
        $payment_line->paid_on = Carbon::now()->toDateTimeString();
        $payment_line->amount = $statement_amount;

        $prefix_type = 'sell_payment';
        $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);
        $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

        return view('vat::customer_statement.pay_statement_amount')->with(compact(
            'statement',
            'statement_amount',
            'statement_amount_display',
            'paid_amount',
            'is_paid',
            'has_payment',
            'statement_display_no',
            'payment_line',
            'payment_types',
            'accounts',
            'default_account_id',
            'payment_ref_no',
            'business_locations',
            'business_location_id'
        ));
    }

    /**
     * Save one full VAT Statement payment and post it to Accounts Receivable
     * and the Customer Ledger. The request amount is intentionally ignored.
     */
    public function payStatementAmount(Request $request, $id)
    {
        abort_unless($this->canPayVatStatement(), 403, 'Unauthorized action.');

        $business_id = $this->resolveBusinessId($request);

        $request->validate([
            'method' => 'required|string|max:200',
            'paid_on' => 'required|string',
            'location_id' => 'required|integer',
            'account_id' => 'required|integer',
            'note' => 'nullable|string|max:1000',
            'document' => 'nullable|file|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $statement = VatCustomerStatement::where('business_id', $business_id)
                ->lockForUpdate()
                ->findOrFail($id);

            $statement_amount = $this->getVatStatementAmount($statement);
            if ($statement_amount <= 0) {
                throw new \RuntimeException('The VAT Statement amount is zero.');
            }

            // This workflow intentionally accepts one full payment only. If an
            // old or interrupted payment exists, it must be removed first so a
            // second full payment can never be posted accidentally.
            if ($this->getVatStatementPaidAmount($statement->id) > 0) {
                throw new \RuntimeException('A payment already exists for this VAT Statement. Delete that payment before adding another one.');
            }

            $contact = Contact::where('business_id', $business_id)
                ->findOrFail($statement->customer_id);

            $paid_on = $this->vatFormatter()->uf_date($request->input('paid_on'), true)
                ?: Carbon::now()->toDateTimeString();

            if (!empty($this->transactionUtil->hasReviewed($request->input('paid_on')))) {
                throw new \RuntimeException(__('lang_v1.review_first'));
            }

            if (!empty($this->transactionUtil->get_review(
                $request->input('paid_on'),
                $request->input('paid_on')
            ))) {
                throw new \RuntimeException("You can't add a payment for an already reviewed date.");
            }

            $location_id = (int) $request->input('location_id');
            $valid_location = BusinessLocation::where('business_id', $business_id)
                ->where('id', $location_id)
                ->exists();
            if (!$valid_location) {
                throw new \RuntimeException('The selected business location is invalid.');
            }

            $method = (string) $request->input('method');
            $valid_payment_methods = $this->transactionUtil->payment_types($location_id);
            unset($valid_payment_methods['credit_sale'], $valid_payment_methods['location_id']);
            if (!array_key_exists($method, $valid_payment_methods)) {
                throw new \RuntimeException('The selected payment method is invalid for this business location.');
            }

            if ($method === 'cheque') {
                if (empty($request->input('cheque_number')) || empty($request->input('bank_name'))) {
                    throw new \RuntimeException('Bank name and cheque number are required for cheque payments.');
                }

                if ($this->transactionUtil->checkCheques($request->input('cheque_number'), $request->input('bank_name')) > 0) {
                    throw new \RuntimeException('A cheque with the same number and bank name already exists.');
                }
            }

            $account_id = (int) $request->input('account_id');
            if ($account_id <= 0) {
                $account_id = (int) Account::where('business_id', $business_id)
                    ->where('name', 'Cash')
                    ->where('is_closed', 0)
                    ->value('id');
            }

            if ($account_id <= 0) {
                throw new \RuntimeException('Please select a valid payment account.');
            }

            $account_belongs_to_business = Account::where('business_id', $business_id)
                ->where('id', $account_id)
                ->where('is_closed', 0)
                ->exists();
            if (!$account_belongs_to_business) {
                throw new \RuntimeException('The selected payment account is invalid.');
            }

            $prefix_type = 'sell_payment';
            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $payment_data = [
                'business_id' => $business_id,
                'amount' => $statement_amount,
                'method' => $method,
                'paid_on' => $paid_on,
                'created_by' => auth()->id(),
                'payment_for' => $contact->id,
                'account_id' => $account_id,
                'payment_ref_no' => $payment_ref_no,
                'paid_in_type' => 'customer_page',
                'linked_vat_customer_statement' => $statement->id,
                'note' => $request->input('note'),
                'card_number' => $request->input('card_number'),
                'card_holder_name' => $request->input('card_holder_name'),
                'card_transaction_number' => $request->input('card_transaction_number'),
                'card_type' => $request->input('card_type'),
                'card_month' => $request->input('card_month'),
                'card_year' => $request->input('card_year'),
                'card_security' => $request->input('card_security'),
                'cheque_number' => $request->input('cheque_number'),
                'cheque_date' => !empty($request->input('cheque_date'))
                    ? $this->vatFormatter()->uf_date($request->input('cheque_date'))
                    : null,
                'bank_account_number' => $request->input('bank_account_number'),
                'bank_name' => $request->input('bank_name'),
                'document' => $this->transactionUtil->uploadFile($request, 'document', 'documents'),
            ];

            if ($method === 'custom_pay_1') {
                $payment_data['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($method === 'custom_pay_2') {
                $payment_data['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($method === 'custom_pay_3') {
                $payment_data['transaction_no'] = $request->input('transaction_no_3');
            }

            $parent_payment = TransactionPayment::create($payment_data);
            $statement_label = $this->normalizeStatementNoForDisplay($statement->statement_no, $business_id);
            $ledger_note = 'VAT Statement No: ' . $statement_label;

            AccountTransaction::createAccountTransaction([
                'business_id' => $business_id,
                'contact_id' => $contact->id,
                'amount' => $statement_amount,
                'account_id' => $account_id,
                'type' => 'debit',
                'operation_date' => $paid_on,
                'created_by' => auth()->id(),
                'transaction_payment_id' => $parent_payment->id,
                'note' => $ledger_note,
            ]);

            $accounts_receivable_id = (int) Account::where('business_id', $business_id)
                ->where('name', 'Accounts Receivable')
                ->where('is_closed', 0)
                ->value('id');

            if ($accounts_receivable_id > 0) {
                AccountTransaction::createAccountTransaction([
                    'business_id' => $business_id,
                    'contact_id' => $contact->id,
                    'amount' => $statement_amount,
                    'account_id' => $accounts_receivable_id,
                    'type' => 'credit',
                    'sub_type' => 'ledger_show',
                    'operation_date' => $paid_on,
                    'created_by' => auth()->id(),
                    'transaction_payment_id' => $parent_payment->id,
                    'note' => $ledger_note,
                ]);
            }

            ContactLedger::createContactLedger([
                'business_id' => $business_id,
                'contact_id' => $contact->id,
                'amount' => $statement_amount,
                'type' => 'credit',
                'sub_type' => 'payment',
                'operation_date' => $paid_on,
                'created_by' => auth()->id(),
                'transaction_payment_id' => $parent_payment->id,
                'note' => $ledger_note,
            ], 'VAT Statement Payment');

            // Allocate the payment to the source invoices so they are removed
            // from the next VAT Statement run and their statuses stay correct.
            $this->transactionUtil->payVATAtOnce($parent_payment, $statement->id);

            DB::commit();

            /*
             |------------------------------------------------------------------
             | S637-4: Payment Received SMS for VAT Statement payments.
             |------------------------------------------------------------------
             |
             | VAT Module -> VAT Statement -> VAT Customers List Statement ->
             | Action -> Pay Statement Amount lands here, and this method had no
             | call to the notification layer, so nothing was ever sent.
             |
             | This is the fourth payment screen with its own controller and the
             | fourth to be missing the call - see S631, S637-2. All of them now
             | build the message through the one shared helper in the Customers
             | module, so the wording and the tag values cannot drift apart.
             |
             |   Paid Amount    = $statement_amount, the figure this payment is
             |                    for. This workflow posts one full payment for
             |                    the statement, and that is the amount written
             |                    to transaction_payments above, so it is what the
             |                    customer should be told they paid.
             |
             |   Ledger Balance = resolved inside the helper AFTER this commit, so
             |                    it is what REMAINS. It reads contact_ledgers -
             |                    the row written a few lines above is therefore
             |                    included - which is the same source the
             |                    Customers screens display. Using
             |                    ContactUtil::getCustomerBalance() instead is
             |                    what produced the mismatched figures in S637-1.
             |
             | After the commit and inside its own try/catch: a dead SMS gateway
             | must never roll back or fail a payment that has already saved.
             */
            try {
                $vat_sms_customer = \Modules\Customers\Entities\Customer::withoutGlobalScopes()
                    ->where('business_id', $business_id)
                    ->find($contact->id);

                if (! empty($vat_sms_customer)
                    && class_exists(\Modules\Customers\Services\CustomerPaymentActionService::class)) {
                    app(\Modules\Customers\Services\CustomerPaymentActionService::class)
                        ->sendCustomerPaymentReceivedNotification(
                            (int) $business_id,
                            $vat_sms_customer,
                            (float) $statement_amount,
                            (string) $paid_on,
                            $payment_ref_no,
                            'VAT Statement Payment'
                        );
                } else {
                    /*
                     * Never fail silently here. A skip that writes nothing is
                     * what made S631 take four attempts to find - "no SMS and no
                     * log" is indistinguishable from "the code never ran".
                     */
                    Log::warning('S637-4: VAT Statement payment SMS skipped.', [
                        'business_id' => $business_id,
                        'statement_id' => $id,
                        'contact_id' => $contact->id ?? null,
                        'reason' => empty($vat_sms_customer)
                            ? 'customer record not found in the Customers module'
                            : 'the Customers module is not installed, so the shared notification builder is unavailable',
                    ]);
                }
            } catch (\Throwable $notification_exception) {
                Log::error('S637-4: Payment Received notification failed after a saved VAT Statement payment.', [
                    'business_id' => $business_id,
                    'statement_id' => $id,
                    'contact_id' => $contact->id ?? null,
                    'payment_id' => $parent_payment->id ?? null,
                    'message' => $notification_exception->getMessage(),
                    'file' => $notification_exception->getFile(),
                    'line' => $notification_exception->getLine(),
                ]);
            }

            $output = [
                'success' => true,
                'msg' => 'VAT Statement payment saved successfully.',
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('VAT Statement payment failed', [
                'statement_id' => $id,
                'business_id' => $business_id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => false,
                'msg' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output, $output['success'] ? 200 : 422);
        }

        return Redirect::to(action('\Modules\Vat\Http\Controllers\CustomerStatementController@index') . '#list_customer_statements')
            ->with('status', $output);
    }

    public function getCustomerStatementList(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);

        if (!$request->ajax()) {
            abort(404);
        }

        $query = VatCustomerStatement::leftJoin('customer_statements', 'customer_statements.id', '=', 'vat_customer_statements.linked_vat_statement')
            ->leftJoin('users as u', 'u.id', '=', 'vat_customer_statements.converted_by')
            ->leftJoin('contacts', 'contacts.id', '=', 'vat_customer_statements.customer_id')
            ->leftJoin('users as added_user', 'added_user.id', '=', 'vat_customer_statements.added_by')
            ->with(['contact', 'user'])
            ->where('vat_customer_statements.business_id', $business_id)
            ->select(
                'vat_customer_statements.*',
                'u.username as user_converted',
                'added_user.username as added_by_username',
                'contacts.name as customer_name',
                'contacts.type as customer_type_value',
                'customer_statements.statement_no as vat_statement',
                'vat_customer_statements.print_date as vat_date'
            )
            ->addSelect([
                'statement_amount' => VatCustomerStatementDetail::selectRaw('COALESCE(SUM(invoice_amount), 0)')
                    ->whereColumn('vat_customer_statement_details.statement_id', 'vat_customer_statements.id'),
                'paid_amount' => TransactionPayment::selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('transaction_payments.linked_vat_customer_statement', 'vat_customer_statements.id')
                    ->whereColumn('transaction_payments.business_id', 'vat_customer_statements.business_id')
                    ->whereNull('transaction_payments.parent_id'),
            ]);

        // Statement periods that overlap the selected date range must be shown.
        if (!empty($request->start_date) && !empty($request->end_date)) {
            $query->where(function ($dateQuery) use ($request) {
                $dateQuery->whereDate('vat_customer_statements.date_to', '>=', $request->start_date)
                    ->whereDate('vat_customer_statements.date_from', '<=', $request->end_date);
            });
        } elseif (!empty($request->start_date)) {
            $query->whereDate('vat_customer_statements.date_to', '>=', $request->start_date);
        } elseif (!empty($request->end_date)) {
            $query->whereDate('vat_customer_statements.date_from', '<=', $request->end_date);
        }
        if (!empty($request->printed_start)) {
            $query->whereDate('vat_customer_statements.print_date', '>=', $request->printed_start);
        }
        if (!empty($request->printed_end)) {
            $query->whereDate('vat_customer_statements.print_date', '<=', $request->printed_end);
        }
        if (!empty($request->location_id)) {
            $location_id = (int) $request->location_id;
            $query->whereExists(function ($subQuery) use ($location_id) {
                $subQuery->select(DB::raw(1))
                    ->from('vat_customer_statement_details as location_details')
                    ->join('transactions as location_transactions', 'location_transactions.id', '=', 'location_details.transaction_id')
                    ->whereColumn('location_details.statement_id', 'vat_customer_statements.id')
                    ->where('location_transactions.location_id', $location_id);
            });
        }
        if (!empty($request->customer_id)) {
            $query->where('vat_customer_statements.customer_id', $request->customer_id);
        }
        if (!empty($request->customer_type) && $request->customer_type !== 'all') {
            $query->where('contacts.type', $request->customer_type);
        }
        if (!empty($request->search_term)) {
            $search_term = trim((string) $request->search_term);
            $query->where(function ($q) use ($search_term) {
                $q->where('vat_customer_statements.statement_no', 'like', '%' . $search_term . '%')
                    ->orWhere('contacts.name', 'like', '%' . $search_term . '%')
                    ->orWhere('added_user.username', 'like', '%' . $search_term . '%');
            });
        }

        // Resolve permissions and subscription settings once for the complete
        // response instead of repeating the same database work for every row.
        $subscription = Subscription::active_subscription($business_id);
        $package_details = !empty($subscription) ? ($subscription->package_details ?? []) : [];
        $enable_126_statement = $this->moduleUtil->hasThePermissionInSubscription(
            $business_id,
            'enable_126_statement'
        );
        $can_pay_statement = $this->canPayVatStatement();
        $can_edit_statement = Gate::allows('edit.vat_statement');
        $can_delete_payments = $this->moduleUtil->hasThePermissionInSubscription(
                $business_id,
                'vat_main_delete_statement_payments'
            )
            && Gate::allows('vat.delete_statement_payment')
            && (!array_key_exists('vat.delete_statement_payment', $package_details)
                || !empty($package_details['vat.delete_statement_payment']));
        $can_delete_statement = $this->moduleUtil->hasThePermissionInSubscription(
                $business_id,
                'vat_main_delete_customer_statement'
            )
            && Gate::allows('vat.delete_customer_statement')
            && (!array_key_exists('vat.delete_customer_statement', $package_details)
                || !empty($package_details['vat.delete_customer_statement']));

        $datatable = DataTables::of($query)
            ->editColumn('print_date', function ($row) {
                return !empty($row->print_date) ? $this->vatFormatter()->format_date($row->print_date) : '';
            })
            ->editColumn('date_from', function ($row) {
                return !empty($row->date_from) ? $this->vatFormatter()->format_date($row->date_from) : '';
            })
            ->editColumn('date_to', function ($row) {
                return !empty($row->date_to) ? $this->vatFormatter()->format_date($row->date_to) : '';
            })
            ->editColumn('statement_no', function ($row) {
                return $this->normalizeStatementNoForDisplay($row->statement_no, $row->business_id);
            })
            ->addColumn('action', function ($row) use (
                $enable_126_statement,
                $can_pay_statement,
                $can_edit_statement,
                $can_delete_payments,
                $can_delete_statement
            ) {
                $statement_amount = round((float) $row->statement_amount + (float) ($row->price_adjustment ?? 0), 6);
                $paid_amount = round((float) $row->paid_amount, 6);
                $has_payment = $paid_amount > 0;

                $html = '<div class="btn-group vat-action-menu-group vat-customer-statement-action-group">'
                    . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                    . __('messages.actions') . ' <span class="caret"></span></button>'
                    . '<ul class="dropdown-menu dropdown-menu-left" role="menu">';

                $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@show", [$row->id]) . '" class="vat-ajax-modal-trigger" data-container=".customer_statement_modal"><i class="fa fa-external-link"></i> ' . __('messages.view') . '</a></li>';
                $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@rePrint", [$row->id]) . '" class="reprint_statement"><i class="fa fa-print"></i> ' . __('contact.print') . '</a></li>';
                $html .= '<li><a href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@exportExcel", [$row->id]) . '"><i class="fa fa-file-excel-o"></i> Excel</a></li>';
                $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@rePrint", [$row->id]) . '" class="pdf_statement"><i class="fa fa-file-pdf-o"></i> ' . __('business.pdf') . '</a></li>';
                $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@rePrint", [$row->id]) . '" class="email_statement"><i class="fa fa-envelope"></i> Email</a></li>';

                if ($enable_126_statement) {
                    $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@print126Statement", [$row->id]) . '" class="print_126_statement"><i class="fa fa-file"></i> 126-Invoice</a></li>';
                }

                $html .= '<li class="divider"></li>';

                if (!$has_payment && $can_pay_statement) {
                    $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@payStatementAmountForm", [$row->id]) . '" class="pay_statement_amount"><i class="fa fa-credit-card"></i> Pay Statement Amount</a></li>';
                }

                if (!$has_payment && $can_edit_statement) {
                    $html .= '<li><a href="#" data-href="' . action("\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@edit", [$row->id]) . '" class="vat-ajax-modal-trigger" data-container=".customer_statement_modal"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                }

                if ($paid_amount > 0 && $can_delete_payments) {
                    $html .= '<li><a href="#" data-href="' . action('\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@destroyPayments', [$row->id]) . '" class="delete_customer_statement"><i class="fa fa-trash"></i> ' . __('lang_v1.delete_payments') . '</a></li>';
                } elseif ($paid_amount <= 0 && $can_delete_statement) {
                    $html .= '<li><a href="#" data-href="' . action('\\Modules\\Vat\\Http\\Controllers\\CustomerStatementController@destroy', [$row->id]) . '" class="delete_customer_statement"><i class="fa fa-trash"></i> ' . __('messages.delete') . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            })
            ->addColumn('description', function ($row) {
                if ((int) $row->is_converted === 1) {
                    $html = "<span class='label label-success'>" . __('contact.converted_from_vat') . "</span><br>";
                    $html .= '<span>' . __('contact.statement_no') . '<b> ' . e($row->vat_statement) . '</b> ' . __('contact.on') . '<b> ' . $this->vatFormatter()->format_date($row->vat_date) . '</b></span><br>';
                    $html .= '<span>' . e($row->user_converted) . '</span>';
                    return $html;
                }

                return '';
            })
            ->addColumn('customer', function ($row) {
                return $row->customer_name ?: optional($row->contact)->name;
            })
            ->addColumn('username', function ($row) {
                return $row->added_by_username ?: optional($row->user)->username;
            })
            ->addColumn('payment_status', function ($row) {
                $statement_amount = round((float) $row->statement_amount + (float) ($row->price_adjustment ?? 0), 6);
                $paid_amount = round((float) $row->paid_amount, 6);

                if ($statement_amount > 0 && $paid_amount + 0.000001 >= $statement_amount) {
                    return '<span class="label vat-statement-status vat-statement-status-paid">Paid</span>';
                }

                return '<span class="label vat-statement-status vat-statement-status-due">Due</span>';
            })
            ->addColumn('amount', function ($row) {
                $amount = round((float) $row->statement_amount + (float) ($row->price_adjustment ?? 0), 6);
                return '<span class="display_currency amount" data-currency_symbol="true" data-orig-value="' . $amount . '">' . $this->vatFormatter()->num_f($amount) . '</span>';
            })
            ->removeColumn('id');

        return $datatable
            ->rawColumns(['action', 'amount', 'payment_status', 'description'])
            ->make(true);
    }

    public function print126Statement(Request $request, $id)
    {
        Log::info('print126Statement');
        $business_id = $this->resolveBusinessId($request);
        $statement = VatCustomerStatement::where('business_id', $business_id)->findOrFail($id);
        $statement->statement_no = $this->normalizeStatementNoForDisplay($statement->statement_no, $statement->business_id);
        $contact   = Contact::where('business_id', $business_id)->findOrFail($statement->customer_id);

        $start_date = $statement->date_from;
        $end_date   = $statement->date_to;

        $statement_details = VatCustomerStatementDetail::leftJoin('transactions', 'transactions.id', 'vat_customer_statement_details.transaction_id')
            ->where('vat_customer_statement_details.business_id', $business_id)
            ->where('statement_id', $id)
            ->select(
                'vat_customer_statement_details.*',
                'transactions.*',
                DB::raw('(vat_customer_statement_details.unit_price * 1.18) as vat_inclusive_unit_price'),
                DB::raw('(vat_customer_statement_details.qty * vat_customer_statement_details.unit_price * 1.18) as vat_inclusive_amount')
            )
            ->get();

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::where('business_id', $business_id)->first();
        $logo             = VatStatementLogo::where('business_id', $business_id)->find($statement->logo);

        Log::info('print126Statement', [$statement, $contact, $start_date, $end_date, $statement_details, $business_details, $location_details, $logo]);

        return view('vat::customer_statement.126_statement')->with(compact(
            'logo',
            'contact',
            'business_details',
            'location_details',
            'statement_details',
            'statement',
            'start_date',
            'end_date'
        ));
    }

    /**
     * Resolve the active business consistently across central and tenant sessions.
     * Some installations expose user.business_id while others expose business.id.
     */
    private function resolveBusinessId(?Request $request = null, $contactId = null): int
    {
        $request = $request ?: request();

        $businessId = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
        );

        if ($businessId <= 0 && !empty($contactId)) {
            $businessId = (int) Contact::whereKey($contactId)->value('business_id');
        }

        abort_if($businessId <= 0, 403, 'Business context is unavailable.');

        return $businessId;
    }

    /**
     * Accept the date-picker value safely and return a database date.
     */
    private function normaliseDateInput($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function __calculateBalanceDue(&$statement_details)
    {
        $total_balance_due = 0;
        
        try {
            foreach ($statement_details as &$detail) {
                // Calculate balance due for this transaction
                if (!empty($detail->transaction_id)) {
                    try {
                        $transaction = Transaction::find($detail->transaction_id);
                        if ($transaction) {
                            $total_paid = TransactionPayment::where('transaction_id', $detail->transaction_id)
                                ->where('is_return', 0)
                                ->sum('amount');
                            
                            $returned_amount = TransactionPayment::where('transaction_id', $detail->transaction_id)
                                ->where('is_return', 1)
                                ->sum('amount');
                            
                            $net_paid = $total_paid - $returned_amount;
                            $balance_due = $detail->invoice_amount - $net_paid;
                            
                            // Ensure balance due is not negative
                            $balance_due = max(0, $balance_due);
                            $total_balance_due += $balance_due;
                            
                            // Update the detail with calculated balance due
                            $detail->balance_due = $balance_due;
                        } else {
                            // Transaction not found, assume full amount is due
                            $detail->balance_due = $detail->invoice_amount;
                            $total_balance_due += $detail->invoice_amount;
                        }
                    } catch (\Exception $e) {
                        // Log error and assume full amount is due
                        \Log::error('Error calculating VAT balance due for transaction ' . $detail->transaction_id . ': ' . $e->getMessage());
                        $detail->balance_due = $detail->invoice_amount;
                        $total_balance_due += $detail->invoice_amount;
                    }
                } else {
                    // If no transaction ID, assume full amount is due
                    $detail->balance_due = $detail->invoice_amount;
                    $total_balance_due += $detail->invoice_amount;
                }
            }
            unset($detail);
        } catch (\Exception $e) {
            // Log error and set default values
            \Log::error('Error calculating VAT statement balance due: ' . $e->getMessage());
            
            // Calculate total from invoice amounts as fallback
            $total_balance_due = 0;
            foreach ($statement_details as &$detail) {
                $detail->balance_due = $detail->invoice_amount;
                $total_balance_due += $detail->invoice_amount;
            }
            unset($detail);
        }

        return $total_balance_due;
    }
}
