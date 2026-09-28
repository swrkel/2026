<?php

namespace Modules\MPCS\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\FormF16Detail;
use Modules\MPCS\Entities\FormF17Detail;
use Modules\MPCS\Entities\FormF17Header;
use Modules\MPCS\Entities\FormF17HeaderController;
use Modules\MPCS\Entities\FormF22Header;
use Modules\MPCS\Entities\Mpcs21cFormSettings;
use App\Contact;
use App\Transaction;
use Modules\MPCS\Entities\Mpcs16aFormSettings;

class F16AFormController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */



    public function index(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $business_id = request()->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? request()->session()->get('business.id');

        abort_if(empty($business_id), 403, 'Business context is missing.');

        // Get settings from dedicated 16A form settings table
        $settings = Mpcs16aFormSettings::where('business_id', $business_id)
        ->latest() // defaults to ordering by 'created_at' descending
        ->first();
       
        $bname = Business::where('id', $business_id)->first();

        // These variables can be used for other parts of the view
        $form_number = optional($settings)->ref_pre_form_number ? $settings->ref_pre_form_number : "";
        $date = optional($settings)->date ? $settings->date : "";
        $userAdded = $bname ? $bname->name : "";      


        $dateRange = $request->input('form_16a_date_range');
        if ($dateRange) {
            $dates = explode(' - ', $dateRange);
            $start_date = $dates[0];
            $end_date = $dates[1];
        } else {
            // Set default date range if the request parameter is empty
            $start_date = Carbon::now()->subDays(7)->format('Y-m-d');
            $end_date = Carbon::now()->format('Y-m-d');
        }

        // New logic to generate F16a_from_no (date-based)
        $today = Carbon::today()->toDateString();
        $F16a_from_no = $this->getF16FormNumberForDate($business_id, $today);


        $suppliers = Contact::suppliersDropdown($business_id, false);
        // S757: F16A is location-specific. Only show locations this user may use,
        // then keep the requested location selected or fall back to the user's
        // first assigned location. This avoids displaying one location while the
        // AJAX request silently runs with an empty/different location.
        $locationQuery = BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('id', 'asc');

        $user = auth()->user();
        $permitted_locations = is_object($user) && method_exists($user, 'permitted_locations')
            ? $user->permitted_locations()
            : 'all';
        if ($permitted_locations !== 'all') {
            $permitted_location_ids = collect($permitted_locations)
                ->map(function ($id) {
                    return (int) $id;
                })
                ->filter()
                ->values()
                ->all();

            if (empty($permitted_location_ids)) {
                $locationQuery->whereRaw('1 = 0');
            } else {
                $locationQuery->whereIn('id', $permitted_location_ids);
            }
        }

        $business_locations = $locationQuery
            ->select(
                DB::raw("IF(location_id IS NULL OR location_id = '', name, CONCAT(name, ' (', location_id, ')')) AS display_name"),
                'id'
            )
            ->pluck('display_name', 'id');

        $requested_location_id = $request->input('location_id', $request->input('16a_location_id'));
        $requested_location_id = $requested_location_id !== null && $requested_location_id !== ''
            ? (int) $requested_location_id
            : null;

        $default_location_id = $requested_location_id !== null && $business_locations->has($requested_location_id)
            ? $requested_location_id
            : $business_locations->keys()->first();
        $default_location_name = $default_location_id !== null
            ? $business_locations->get($default_location_id)
            : '';
        $invoiceno = FormF16Detail::pluck('invoice_no')->toArray();
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();

        $setting = MpcsFormSetting::where('business_id', $business_id)->first();
        $max_form_no = FormF16Detail::max('form_no');
        $all_form_no = FormF16Detail::pluck('form_no')->toArray();
        $max_form_nos = $max_form_no + 1;

        $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join('business_locations AS BS', 'transactions.location_id', '=', 'BS.id')
            ->leftJoin('transaction_payments AS TP', 'transactions.id', '=', 'TP.transaction_id')
            ->leftJoin('transactions AS PR', 'transactions.id', '=', 'PR.return_parent_id')
            ->leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->leftJoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftJoin('variations', 'products.id', 'variations.product_id')
            ->leftJoin('form_f17_details', function($join) use ($business_id) {
                $join->on('form_f17_details.product_id', '=', 'products.id')
                     ->whereRaw('form_f17_details.id = (
                         SELECT fd.id 
                         FROM form_f17_details fd
                         JOIN form_f17_headers fh ON fd.header_id = fh.id
                         WHERE fd.product_id = products.id AND fh.business_id = ?
                         ORDER BY fd.id DESC 
                         LIMIT 1
                     )', [$business_id]);
            })
            ->leftJoin('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.status', 'received')
            ->select(
                'transactions.id',
                'transactions.invoice_no as invoice',
                'transactions.ref_no as reference_no',
                'purchase_lines.quantity as received_qty',
                'purchase_lines.purchase_price as unit_purchase_price',
                DB::raw('purchase_lines.purchase_price * purchase_lines.quantity as total_purchase_price'),
                'BS.name as location',
                'contacts.name as name',
                'transactions.updated_at as date',
                'products.name as product',
                'products.id as product_id',
                'variations.sell_price_inc_tax',
                'form_f17_details.new_price as f17_new_price',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'PR.id as return_transaction_id',
                DB::raw('SUM(TP.amount) as amount_paid'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE TP2.transaction_id=PR.id ) as return_paid'),
                DB::raw('COUNT(PR.id) as return_exists'),
                DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
            )
            ->groupBy('transactions.id')
            ->get()
            ->map(function ($purchase) {
                // For F16 VAT sale price, prefer latest F17 override, otherwise use variation sell price (inc. tax).
                $unitSalePrice = !empty($purchase->f17_new_price) && $purchase->f17_new_price > 0
                    ? $purchase->f17_new_price
                    : $purchase->sell_price_inc_tax;

                $purchase->default_sell_price = $unitSalePrice;

                return $purchase->toArray();
            })
            ->toArray();

        $lastRecord = end($purchases);
        $name = $lastRecord['name'] ?? '';
        $invoice = $lastRecord['invoice'] ?? '';

        return view('mpcs::forms.F16A')->with(compact(
            'business_locations',
            'F16a_from_no',
            'sub_categories',
            'setting',
            'settings',
            'suppliers',
            'invoiceno',
            'all_form_no',
            'lastRecord',
            'form_number',
            'date',
            'userAdded',           
            'name',
            'invoice',
            'default_location_id',
            'default_location_name'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */

    public function saveF16Form(Request $request)
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');

        // Validate required fields so we return a clear message instead of a DB exception
        $transactionId = $request->transaction_id;
        $formNumber = $request->form_number;
        if (empty($transactionId) || trim((string) $transactionId) === '') {
            return response()->json([
                'success' => 0,
                'msg' => 'Please select a purchase row first. Transaction / Purchase order is required.',
            ]);
        }
        if ($formNumber === null || $formNumber === '' || (is_string($formNumber) && trim($formNumber) === '')) {
            return response()->json([
                'success' => 0,
                'msg' => 'Form number is required. Please ensure the form is loaded with a date.',
            ]);
        }

        try {
            DB::beginTransaction();

            $data_details = [
                'transaction_id' => (int) $transactionId,
                'form_no' => (int) $formNumber,
                'invoice_no' => $request->form_invoice ?? '',
                'supplier' => $request->formSupplier ?? '',
                'this_form_total' => (string) ($request->thisformtotal ?? '0'),
                'last_form_total' => (string) ($request->prevformtotal ?? '0'),
                'grand_total' => (string) ($request->grandformtotal ?? '0'),
                'book_no' => (string) ($request->stockNo ?? ''),
                'book_stock' => (string) ($request->stockBook ?? ''),
                'this_book' => (string) ($request->thisbook ?? ''),
                'prev_book' => (string) ($request->prevbook ?? ''),
                'grand_book' => (string) ($request->grandbook ?? ''),
            ];

            $existingRecord = FormF16Detail::where('transaction_id', (int) $transactionId)->first();

            if ($existingRecord) {
                $existingRecord->update($data_details);
            } else {
                // Table form_f16_details has no business_id column; do not set it to avoid DB error
                $details = FormF16Detail::create($data_details);
            }

            DB::commit();

            return response()->json([
                'success' => 1,
                'msg' => __('customer.'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('F16A save error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong') . ' (' . $e->getMessage() . ')',
            ]);
        }
    }
    public function getPreviousValue16a(Request $request)
    {
        $businessId = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id');
        $selectedDate = Carbon::parse($request->input('start_date'))->format('Y-m-d');
        $previousDate = Carbon::parse($selectedDate)->subDay()->format('Y-m-d');
        $locationId = $request->filled('location_id') ? (int) $request->location_id : null;

        /*
         * First use the previous calendar day's saved Grand Total. This is the
         * authoritative value when the prior F16A form was saved.
         */
        $previousForm = FormF16Detail::query()
            ->join('transactions as t', 't.id', '=', 'form_f16_details.transaction_id')
            ->where('t.business_id', $businessId)
            ->whereDate('t.transaction_date', $previousDate)
            ->when($locationId, function ($query) use ($locationId) {
                $query->where('t.location_id', $locationId);
            })
            ->orderByDesc('form_f16_details.id')
            ->select([
                'form_f16_details.grand_total',
                'form_f16_details.grand_book',
                'form_f16_details.form_no',
            ])
            ->first();

        if ($previousForm) {
            return response()->json([
                'pre_total_purchase_price' => (float) ($previousForm->grand_total ?? 0),
                'pre_total_sale_price' => (float) ($previousForm->grand_book ?? 0),
                'previous_date' => $previousDate,
                'previous_form_no' => $previousForm->form_no,
                'source' => 'saved_previous_day_grand_total',
            ]);
        }

        /*
         * A day can have no saved F16A detail rows. The carry must nevertheless
         * remain correct, so reconstruct the cumulative Grand Total from the
         * applicable opening setting plus all received purchases up to yesterday.
         */
        $setting = Mpcs16aFormSettings::where('business_id', $businessId)
            ->whereDate('date', '<=', $previousDate)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->first();

        $purchaseGrand = (float) ($setting->total_purchase_price_with_vat ?? 0);
        $saleGrand = (float) ($setting->total_sale_price_with_vat ?? 0);
        $openingDate = $setting && $setting->date
            ? Carbon::parse($setting->date)->format('Y-m-d')
            : null;

        if ($openingDate && Carbon::parse($previousDate)->gte(Carbon::parse($openingDate))) {
            $purchaseQuery = DB::table('transactions as t')
                ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
                ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
                ->where('t.business_id', $businessId)
                ->where('t.type', 'purchase')
                ->where('t.status', 'received')
                ->whereDate('t.transaction_date', '>=', $openingDate)
                ->whereDate('t.transaction_date', '<=', $previousDate)
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('t.location_id', $locationId);
                });

            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $purchaseQuery->whereNull('t.deleted_at');
            }
            if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
                $purchaseQuery->whereNull('pl.deleted_at');
            }

            $purchasePriceParts = [];
            if (Schema::hasColumn('purchase_lines', 'purchase_price_inc_tax')) {
                $purchasePriceParts[] = 'NULLIF(pl.purchase_price_inc_tax, 0)';
            }
            if (Schema::hasColumn('purchase_lines', 'purchase_price')) {
                if (Schema::hasColumn('purchase_lines', 'item_tax')) {
                    $purchasePriceParts[] = '(COALESCE(pl.purchase_price, 0) + COALESCE(pl.item_tax, 0))';
                } else {
                    $purchasePriceParts[] = 'pl.purchase_price';
                }
            }
            $purchasePriceParts[] = '0';
            $purchasePrice = 'COALESCE(' . implode(', ', $purchasePriceParts) . ')';

            $salePriceParts = [];
            if (Schema::hasColumn('purchase_lines', 'sell_price_at_purchase')) {
                $salePriceParts[] = 'NULLIF(pl.sell_price_at_purchase, 0)';
            }
            if (Schema::hasColumn('variations', 'sell_price_inc_tax')) {
                $salePriceParts[] = 'NULLIF(v.sell_price_inc_tax, 0)';
            }
            if (Schema::hasColumn('variations', 'default_sell_price')) {
                $salePriceParts[] = 'NULLIF(v.default_sell_price, 0)';
            }
            $salePriceParts[] = '0';
            $salePrice = 'COALESCE(' . implode(', ', $salePriceParts) . ')';

            $totals = $purchaseQuery
                ->selectRaw(
                    "COALESCE(SUM(($purchasePrice) * COALESCE(pl.quantity, 0)), 0) AS purchase_total, " .
                    "COALESCE(SUM(($salePrice) * COALESCE(pl.quantity, 0)), 0) AS sale_total"
                )
                ->first();

            $purchaseGrand += (float) ($totals->purchase_total ?? 0);
            $saleGrand += (float) ($totals->sale_total ?? 0);
        }

        return response()->json([
            'pre_total_purchase_price' => $purchaseGrand,
            'pre_total_sale_price' => $saleGrand,
            'previous_date' => $previousDate,
            'previous_form_no' => null,
            'source' => 'calculated_cumulative_grand_total',
        ]);
    }

    public function getF16FormList(Request $request)
    {


        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        if (request()->ajax()) {
            $header = FormF16Detail::select('form_f16_details.*');

            if (!empty(request()->form_no) && request()->form_no !== 'All') {

                $header->where('form_f16_details.form_no', $request->form_no);
            }
            if (!empty(request()->invoice_no) && request()->invoice_no !== 'All') {

                $header->where('form_f16_details.invoice_no', $request->invoice_no);
            }
            if (!empty(request()->supplier) && request()->supplier !== 'All') {
                $supplierStart = substr($request->supplier, 0, 5);
                //$header->where('form_f16_details.supplier', $request->supplier);
                $header->where('form_f16_details.supplier', 'like', $supplierStart . '%');
            }

            if (!empty($request->input('start_date')) && !empty($request->input('end_date'))) {
                $start_date = $request->start_date;
                $end_date = $request->end_date;

                // Normalize incoming date format: accept d/m/Y (from UI) or Y-m-d
                if (!empty($start_date)) {
                    if (\Carbon\Carbon::hasFormat($start_date, 'd/m/Y')) {
                        $start_date = \Carbon\Carbon::createFromFormat('d/m/Y', $start_date)->format('Y-m-d');
                    }
                }

                if (!empty($end_date)) {
                    if (\Carbon\Carbon::hasFormat($end_date, 'd/m/Y')) {
                        $end_date = \Carbon\Carbon::createFromFormat('d/m/Y', $end_date)->format('Y-m-d');
                    }
                }

                $header->whereDate('form_f16_details.created_at', '>=', $start_date);
                $header->whereDate('form_f16_details.created_at', '<=', $end_date);
            }

            return Datatables::of($header)
                ->addIndexColumn()

                ->removeColumn('id')
                ->editColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $actionId = $row->transaction_id ?: $row->id;
                    $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F16AFormController@view', [$actionId]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>';
                    $html .= '<li><a href="' . action('\Modules\MPCS\Http\Controllers\F16AFormController@edit', [$actionId]) . '"><i class="fa fa-edit" aria-hidden="true"></i>' . __("messages.edit") . '</a></li>';

                    $html .= '</ul>';
                    return $html;
                })

                ->rawColumns(['action'])
                ->make(true);
        }
    }
    public function printF16Form(Request $request)
    {


        return view('mpcs::forms.partials.list_f16');
    }
    public function view(Request $request)
    {

        $transaction_id = $request->id;

        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

        $setting = $transactionDetails['settings'];
        $suppliers = $transactionDetails['suppliers'];
        $lastRecord = $transactionDetails['lastRecord'];
        $name = $transactionDetails['name'];
        $invoice = $transactionDetails['invoice'];
        $business_locations = $transactionDetails['business_locations'];
        $F16a_from_no = $transactionDetails['F16a_from_no'];
        $sub_categories = $transactionDetails['sub_categories'];
        $invoiceno = $transactionDetails['invoiceno'];
        $all_form_no = $transactionDetails['all_form_no'];
        $form_f16a = $transactionDetails['form_f16a'];
        return view('mpcs::forms.partials.16a_view')->with(compact(
            'business_locations',
            'F16a_from_no',
            'sub_categories',
            'setting',
            'suppliers',
            'invoiceno',
            'all_form_no',
            'lastRecord',
            'name',
            'invoice',
            'form_f16a'
        ));
    }

    public function getTransactionDetails($transaction_id, $business_id)
    {
        $settings16a = Mpcs16aFormSettings::where('business_id', $business_id)->latest()->first();
        $F16a_from_no = $this->getF16FormNumberForDate($business_id, Carbon::today()->toDateString());

        $suppliers = Contact::suppliersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id, true); // Show "All" option
        $invoiceno = FormF16Detail::pluck('invoice_no')->toArray();
        $sub_categories = Category::where('business_id', $business_id)->where('parent_id', '!=', 0)->get();
        $form_f16a = FormF16Detail::where('transaction_id', $transaction_id)->first();
        $setting = MpcsFormSetting::where('business_id', $business_id)->first();
        $max_form_no = FormF16Detail::max('form_no');
        $all_form_no = FormF16Detail::pluck('form_no')->toArray();
        $max_form_nos = $max_form_no + 1;

        // If editing/viewing an existing form, keep its number; otherwise ensure at least starting number from settings
        if (!empty($form_f16a)) {
            $F16a_from_no = $form_f16a->form_no;
        } elseif (!empty($settings16a)) {
            $F16a_from_no = $this->getF16FormNumberForDate($business_id, Carbon::today()->toDateString());
        }

        if ($max_form_nos >= $F16a_from_no) {
            $F16a_from_no = $max_form_nos;
        }



        $purchases = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
            ->join(
                'business_locations AS BS',
                'transactions.location_id',
                '=',
                'BS.id'
            )
            ->leftJoin(
                'transaction_payments AS TP',
                'transactions.id',
                '=',
                'TP.transaction_id'
            )
            ->leftJoin(
                'transactions AS PR',
                'transactions.id',
                '=',
                'PR.return_parent_id'
            )
            ->leftjoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            ->leftjoin('products', 'purchase_lines.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftJoin('form_f17_details', function($join) use ($business_id) {
                $join->on('form_f17_details.product_id', '=', 'products.id')
                     ->whereRaw('form_f17_details.id = (
                         SELECT fd.id 
                         FROM form_f17_details fd
                         JOIN form_f17_headers fh ON fd.header_id = fh.id
                         WHERE fd.product_id = products.id AND fh.business_id = ?
                         ORDER BY fd.id DESC 
                         LIMIT 1
                     )', [$business_id]);
            })
            ->leftJoin('form_f17_headers', 'form_f17_details.header_id', '=', 'form_f17_headers.id')
            ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.id', $transaction_id)
            ->where('transactions.status', 'received')
            ->select(
                'transactions.id',
                'transactions.invoice_no as invoice',
                'transactions.ref_no as reference_no',
                'purchase_lines.quantity as received_qty',
                'purchase_lines.purchase_price as unit_purchase_price',
                DB::raw('purchase_lines.purchase_price * purchase_lines.quantity as total_purchase_price'),
                'BS.name as location',
                'contacts.name as name',
                'transactions.updated_at as date',
                'products.name as product',
                'products.id as product_id',
                'variations.sell_price_inc_tax',
                'form_f17_details.new_price as f17_new_price',
                'transactions.pay_term_number',
                'transactions.pay_term_type',
                'PR.id as return_transaction_id',
                DB::raw('SUM(TP.amount) as amount_paid'),
                DB::raw('(SELECT SUM(TP2.amount) FROM transaction_payments AS TP2 WHERE
                    TP2.transaction_id=PR.id ) as return_paid'),
                DB::raw('COUNT(PR.id) as return_exists'),
                DB::raw('COALESCE(PR.final_total, 0) as amount_return'),
                DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by")
            )
            ->groupBy('transactions.id')
            ->get()
            ->map(function ($purchase) {
                // For F16 VAT sale price, prefer latest F17 override, otherwise use variation sell price (inc. tax).
                $unitSalePrice = !empty($purchase->f17_new_price) && $purchase->f17_new_price > 0
                    ? $purchase->f17_new_price
                    : $purchase->sell_price_inc_tax;

                $purchase->default_sell_price = $unitSalePrice;

                return $purchase->toArray();
            })
            ->toArray();

        $lastRecord = end($purchases);
        $name = $lastRecord['name'];
        $invoice = $lastRecord['invoice'];
        return [
            'settings' => $setting,
            'suppliers' => $suppliers,
            'business_locations' => $business_locations,
            'invoiceno' => $invoiceno,
            'sub_categories' => $sub_categories,
            'form_f16a' => $form_f16a,
            'setting' => $setting,
            'F16a_from_no' => $F16a_from_no,
            'purchases' => $purchases,
            'lastRecord' => $lastRecord,
            'name' => $name,
            'invoice' => $invoice,
            'all_form_no' => $all_form_no
        ];
    }
    public function edit(Request $request)
    {
        $transaction_id = $request->id;

        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

        $setting = $transactionDetails['settings'];
        $suppliers = $transactionDetails['suppliers'];
        $lastRecord = $transactionDetails['lastRecord'];
        $name = $transactionDetails['name'];
        $invoice = $transactionDetails['invoice'];
        $business_locations = $transactionDetails['business_locations'];
        $F16a_from_no = $transactionDetails['F16a_from_no'];
        $sub_categories = $transactionDetails['sub_categories'];
        $invoiceno = $transactionDetails['invoiceno'];
        $all_form_no = $transactionDetails['all_form_no'];
        $form_f16a = $transactionDetails['form_f16a'];
        $edit = 'edit';
        return view('mpcs::forms.partials.16a_edit')->with(compact(
            'business_locations',
            'F16a_from_no',
            'sub_categories',
            'setting',
            'suppliers',
            'invoiceno',
            'all_form_no',
            'lastRecord',
            'name',
            'invoice',
            'edit',
            'form_f16a'
        ));
    }
    public function print(Request $request)
    {


        $form_f16a = FormF16Detail::where('form_no', $request->formId)->first();
        $transaction_id = $form_f16a['transaction_id'];
        $business_id = request()->session()->get('business.id');
        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $transactionDetails =  $this->getTransactionDetails($transaction_id, $business_id);

        $setting = $transactionDetails['settings'];
        $suppliers = $transactionDetails['suppliers'];
        $lastRecord = $transactionDetails['lastRecord'];
        $name = $transactionDetails['name'];
        $invoice = $transactionDetails['invoice'];
        $business_locations = $transactionDetails['business_locations'];
        $F16a_from_no = $transactionDetails['F16a_from_no'];
        $sub_categories = $transactionDetails['sub_categories'];
        $invoiceno = $transactionDetails['invoiceno'];
        $all_form_no = $transactionDetails['all_form_no'];
        return view('mpcs::forms.partials.print_f16_form')->with(compact(
            'business_locations',
            'F16a_from_no',
            'sub_categories',
            'setting',
            'suppliers',
            'invoiceno',
            'all_form_no',
            'lastRecord',
            'name',
            'invoice',
            'form_f16a'
        ));
    }

    /**
     * Save all purchase transactions for a given date to form_f16_details.
     * This enables the F21 form to resolve "F 16 A / {form_no}" for each purchase.
     */
    public function saveAllF16Forms(Request $request)
    {
        $business_id = request()->session()->get('user.business_id') ?? request()->session()->get('business.id');
        $date = $request->input('date');
        $form_no = $request->input('form_no');
        $location_id = $request->input('location_id');
        $this_form_total = (string) ($request->input('this_form_total', '0'));
        $last_form_total = (string) ($request->input('last_form_total', '0'));
        $grand_total = (string) ($request->input('grand_total', '0'));

        if (empty($date) || $form_no === null || $form_no === '') {
            return response()->json([
                'success' => 0,
                'msg' => 'Date and form number are required.',
            ]);
        }

        try {
            DB::beginTransaction();

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('status', 'received')
                ->whereDate('transaction_date', $date);

            if (!empty($location_id)) {
                $query->where('location_id', $location_id);
            }

            $transactions = $query->with('contact')->get();

            if ($transactions->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => 0,
                    'msg' => 'No purchase transactions found for the selected date.',
                ]);
            }

            $count = 0;
            foreach ($transactions as $transaction) {
                FormF16Detail::updateOrCreate(
                    ['transaction_id' => $transaction->id],
                    [
                        'form_no' => (int) $form_no,
                        'supplier' => substr(optional($transaction->contact)->name ?? '', 0, 50),
                        'invoice_no' => substr($transaction->invoice_no ?? '', 0, 20),
                        'this_form_total' => $this_form_total,
                        'last_form_total' => $last_form_total,
                        'grand_total' => $grand_total,
                    ]
                );
                $count++;
            }

            DB::commit();

            return response()->json([
                'success' => 1,
                'msg' => $count . ' record(s) saved successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('F16A bulk save error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong') . ' (' . $e->getMessage() . ')',
            ]);
        }
    }

    /**
     * Calculate the F16A form number for a given business and date.
     * Form number = starting_number + days since opening date (clamped to starting_number for earlier dates).
     */
    protected function getF16FormNumberForDate($businessId, $date)
    {
        $settings = Mpcs16aFormSettings::where('business_id', $businessId)->latest()->first();

        if (empty($settings) || empty($settings->starting_number) || empty($settings->date)) {
            return 1;
        }

        $openingDate = Carbon::parse($settings->date);
        $selectedDate = Carbon::parse($date);

        if ($selectedDate->lt($openingDate)) {
            return (int) $settings->starting_number;
        }

        $daysDiff = $openingDate->diffInDays($selectedDate);

        return (int) $settings->starting_number + $daysDiff;
    }

    /**
     * AJAX endpoint: return form number for the supplied date.
     */
    public function fetchFormNumber(Request $request)
    {
        $businessId = $request->session()->get('business.id');
        $date = $request->input('date', Carbon::today()->toDateString());

        return response()->json([
            'form_number' => $this->getF16FormNumberForDate($businessId, $date),
        ]);
    }

    /**
     * Get products for F16A product filter dropdown
     * Returns products used in purchase transactions for Select2 dropdown
     */
    public function getF16AProducts(Request $request)
    {
        $business_id = session()->get('business.id');
        $search = $request->input('search', '');
        $page = $request->input('page', 1);

        $query = Product::where('business_id', $business_id)
            ->where('type', '!=', 'modifier')
            ->select('id', 'name')
            ->orderBy('name');

        if (!empty($search)) {
            // Optimize search: first check exact matches, then prefix matches, then contains
            $query->where(function($q) use ($search) {
                $q->where('name', '=', $search)
                  ->orWhere('name', 'LIKE', $search . '%')
                  ->orWhere('name', 'LIKE', '%' . $search . '%');
            });
        }

        $perPage = 30; // Increased from 20 to show more results per request
        
        // Use count() only when needed, more efficient for large datasets
        $products = $query->skip(($page - 1) * $perPage)
            ->limit($perPage + 1) // Get one extra to check if there are more
            ->get();

        // Check if there are more results without separate count query
        $hasMore = $products->count() > $perPage;
        if ($hasMore) {
            $products = $products->slice(0, $perPage);
        }

        $result = [
            'results' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'text' => $product->name
                ];
            })->values(),
            'pagination' => [
                'more' => $hasMore
            ]
        ];

        return response()->json($result);
    }
}
