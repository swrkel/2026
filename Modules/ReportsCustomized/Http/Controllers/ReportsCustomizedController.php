<?php

namespace Modules\ReportsCustomized\Http\Controllers;

use App\Business;
use App\Category;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Product;
use App\SalesAgent;
use App\Transaction;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\DistributionInvoiceCheque;
use Modules\Distribution\Entities\DistributionInvoiceLine;
use Modules\Distribution\Entities\Distribution_discount_product;
use Illuminate\Contracts\Support\Renderable;
use Modules\ReportsCustomized\Entities\LiocReportCustomized;
use Modules\ReportsCustomized\Entities\SavedLiocStatement;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\System;

class ReportsCustomizedController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    protected $transactionUtil;

    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }
   public function index(Request $request)
{
    $business_id = request()->session()->get('user.business_id');
    $settings = LiocReportCustomized::where('business_id', $business_id)
                ->orderBy('created_at', 'desc')
                ->first();

    $business = Business::find($business_id);
    $location =  \App\BusinessLocation::where('business_id', $business_id)
                ->where('is_active', 1)
                ->first();

    if (!$location) {
        $location =  \App\BusinessLocation::where('business_id', $business_id)->first();
    }

    // Parse date range from request
    $start_date = $request->get('start_date');
    $end_date = $request->get('end_date');

    // Default to current month if not provided
    if (empty($start_date)) {
        $start_date = Carbon::now()->startOfMonth()->toDateString();
    } else {
        $start_date = Carbon::parse($start_date)->toDateString();
    }
    if (empty($end_date)) {
        $end_date = Carbon::now()->endOfMonth()->toDateString();
    } else {
        $end_date = Carbon::parse($end_date)->toDateString();
    }

    $period_display = Carbon::parse($start_date)->format('dmY') . ' To ' . Carbon::parse($end_date)->format('dmY');

    $type = ['sell'];
    $status = ['final'];
    $is_credit_sale = [1];
    $payment_types = $this->transactionUtil->payment_types(null, false, false, false, true, "is_sale_enabled");

    $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
        ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
        ->leftJoin('transaction_sell_lines as tsl', 'transactions.id', '=', 'tsl.transaction_id')
        ->leftJoin('products', 'tsl.product_id', '=', 'products.id')
        ->leftJoin('users as u', 'transactions.created_by', '=', 'u.id')
        ->leftJoin('users as ss', 'transactions.res_waiter_id', '=', 'ss.id')
        ->leftjoin('users as deleted', 'transactions.deleted_by', 'deleted.id')
        ->leftJoin('res_tables as tables', 'transactions.res_table_id', '=', 'tables.id')
        ->join('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
        ->leftJoin('transactions AS SR', 'transactions.id', '=', 'SR.return_parent_id')
        ->leftJoin('types_of_services AS tos', 'transactions.types_of_service_id', '=', 'tos.id')
        ->select(
            'transactions.id',
            'transactions.transaction_date',
            'transactions.is_direct_sale',
            'transactions.invoice_no',
            'contacts.name as cusname',
            'contacts.mobile',
            'transactions.price_later',
            'transactions.payment_status',
            'transactions.is_credit_sale',
            'transactions.final_total',
            'transactions.tax_amount',
            'transactions.discount_amount',
            'transactions.discount_type',
            'transactions.total_before_tax',
            'transactions.rp_redeemed',
            'transactions.rp_redeemed_amount',
            'transactions.rp_earned',
            'transactions.types_of_service_id',
            'transactions.shipping_status',
            'transactions.pay_term_number',
            'transactions.pay_term_type',
            'transactions.additional_notes',
            'transactions.staff_note',
            'transactions.shipping_details',
            'transactions.commission_agent',
            'transactions.ref_no as ref_no',
            'transactions.sub_type as the_transaction_sub_type',
            'deleted.username as deletedBy',
            DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
            DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id = transactions.id AND deleted_at IS NULL) as total_paid'),
            'bl.name as business_location',
            DB::raw('COALESCE((SELECT COUNT(*) FROM transactions WHERE return_parent_id = transactions.id), 0) as return_exists'),
            DB::raw('(SELECT COALESCE(SUM(amount), 0) FROM transaction_payments WHERE transaction_id IN (SELECT id FROM transactions WHERE return_parent_id = transactions.id)) as return_paid'),
            DB::raw('COALESCE((SELECT final_total FROM transactions WHERE return_parent_id = transactions.id LIMIT 1), 0) as amount_return'),
            DB::raw('(SELECT id FROM transactions WHERE return_parent_id = transactions.id LIMIT 1) as return_transaction_id'),
            'tos.name as types_of_service_name',
            'transactions.service_custom_field_1',
            DB::raw('(SELECT COUNT(*) FROM transaction_sell_lines WHERE transaction_id = transactions.id) as total_items'),
            DB::raw("CONCAT(COALESCE(ss.surname, ''),' ',COALESCE(ss.first_name, ''),' ',COALESCE(ss.last_name,'')) as waiter"),
            'tables.name as table_name'
        )
        ->where('transactions.business_id', $business_id) // Always scope by business
        ->whereIn('transactions.status', $status)
        ->whereIn('transactions.is_credit_sale', $is_credit_sale)
        ->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']) // Date filter added
        ->with(['sell_lines', 'sell_lines.product'])
        ->groupBy('transactions.id') // Group by to avoid duplicates
        ->orderBy('transactions.id', 'DESC');

    $final_sells = $sells->get();
    $total_amount = $final_sells->sum('final_total');

    // If it's an AJAX request, return only the table partial
    if ($request->ajax()) {
        $html = view('reportscustomized::partials.sales_table_rows', compact('final_sells', 'settings', 'period_display'))->render();
        return response()->json([
            'html' => $html,
            'total_amount' => $total_amount,
            'period_display' => $period_display
        ]);
    }

    // Modified by Engr. Alex -- task 7882: Issue 6 - fetch report footer from Super Admin Application Settings
    $report_footer = System::getProperty('admin_reports_footer');

    // Modified by Engr. Alex -- task 7882: Issue 5 - pass dropdown lists for List LIOC Statements filters
    $statement_nos = Transaction::where('business_id', $business_id)
        ->whereIn('status', ['final'])
        ->whereIn('is_credit_sale', [1])
        ->whereNotNull('invoice_no')
        ->orderBy('invoice_no')
        ->pluck('invoice_no', 'invoice_no')
        ->prepend('All', '');

    $customers_list = \App\Contact::where('business_id', $business_id)
        ->where('type', 'customer')
        ->orderBy('name')
        ->pluck('name', 'id')
        ->prepend('All', '');

    // Modified by Engr. Alex -- task 7882: Issue 5 iv - vehicle order nos from customer_references.reference
    $vehicle_order_nos = \App\CustomerReference::where('business_id', $business_id)
        ->whereNotNull('reference')
        ->where('reference', '!=', '')
        ->orderBy('reference')
        ->distinct()
        ->pluck('reference', 'reference')
        ->prepend('All', '');

    return view('reportscustomized::index')->with(compact(
        'business', 'location', 'final_sells', 'total_amount',
        'settings', 'period_display',
        'statement_nos', 'customers_list', 'vehicle_order_nos',
        'report_footer'
    ));
}

    // Modified by Engr. Alex -- task 7882: Issue 5 - DataTables endpoint for List LIOC Statements
    // Modified by Engr. Alex -- task 7882: fix – use SavedLiocStatement (matches partial columns); single Edit button per statement row
    public function listStatements(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Bad request'], 400);
        }

        $business_id = (int) request()->session()->get('user.business_id');

        $query = SavedLiocStatement::query()
            ->where('saved_lioc_statements.business_id', $business_id)
            ->leftJoin('users as u', 'saved_lioc_statements.created_by', '=', 'u.id')
            ->select(
                'saved_lioc_statements.id',
                'saved_lioc_statements.created_at',
                'saved_lioc_statements.period_display',
                'saved_lioc_statements.bill_ref_display',
                'saved_lioc_statements.total_amount',
                'saved_lioc_statements.line_count',
                DB::raw("CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) as added_by")
            );

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->startOfDay();
            $end   = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('saved_lioc_statements.created_at', [$start, $end]);
        } else {
            $query->whereBetween('saved_lioc_statements.created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        }

        // Statement No filter
        if ($request->filled('statement_no') && $request->statement_no !== '') {
            $query->where('saved_lioc_statements.bill_ref_display', $request->statement_no);
        }

        $query->orderByDesc('saved_lioc_statements.id');

        // Modified by Engr. Alex -- task 7882: Issue 8 - single Edit button per row, only for permitted users (no foreach loop)
        $can_edit = auth()->user()->can('cr.edit_lioc_statement');

        return DataTables::of($query)
            ->addColumn('action', function ($row) use ($can_edit) {
                $statementUrl = route('reportscustomized.saved-statement', ['id' => $row->id]);

                $html = '<div class="btn-group">';
                $html .= '<a href="' . e($statementUrl) . '" target="_blank" class="btn btn-xs btn-info">'
                    . '<i class="fa fa-eye"></i> ' . __('messages.view') . '</a>';

                if ($can_edit) {
                    $html .= ' <a href="' . e($statementUrl) . '" target="_blank" class="btn btn-xs btn-primary">'
                        . '<i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a>';
                }

                $html .= '</div>';
                return $html;
            })
            ->editColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('d.m.Y H:i');
            })
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->total_amount, 2);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('reportscustomized::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('reportscustomized::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('reportscustomized::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
