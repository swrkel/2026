<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\Contact;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\User;
use Modules\Distribution\Utils\ModuleUtil;
use Modules\Distribution\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\Distribution_routes;
use Modules\Distribution\Entities\DistributionVehicles;
use Yajra\DataTables\Facades\DataTables;

class DistributionInvoiceListController extends Controller
{
    protected $moduleUtil;
    protected $transactionUtil;

    public function __construct(ModuleUtil $moduleUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     * Involved Tables: distribution_invoices, contacts, users, distribution_routes, distribution_vehicles
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('distribution.view_invoices') && !auth()->user()->can('distribution.view_all_invoices')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $query = DistributionInvoice::where('distribution_invoices.business_id', $business_id)
                ->leftJoin('contacts', 'distribution_invoices.customer_id', '=', 'contacts.id')
                ->leftJoin('users as added_by_user', 'distribution_invoices.added_by', '=', 'added_by_user.id')
                ->leftJoin('users as updated_by_user', 'distribution_invoices.updated_by', '=', 'updated_by_user.id')
                ->leftJoin('distribution_routes', 'distribution_invoices.route_id', '=', 'distribution_routes.id')
                ->leftJoin('distribution_vehicles', 'distribution_invoices.vehicle_id', '=', 'distribution_vehicles.id')
                ->leftJoin('transactions as T', function ($join) use ($business_id) {
                    $join->on('distribution_invoices.invoice_no', '=', 'T.invoice_no')
                        ->where('T.business_id', $business_id)
                        ->where('T.type', 'sell');
                })
                ->leftJoin('transactions as SR', 'T.id', '=', 'SR.return_parent_id')
                ->select([
                    'distribution_invoices.*',
                    'contacts.name as customer_name_orig',
                    'contacts.contact_id as customer_code',
                    'contacts.mobile as customer_mobile',
                    'added_by_user.username as added_by_username',
                    'updated_by_user.username as updated_by_username',
                    'distribution_routes.name as route_name',
                    'distribution_vehicles.vehicle_no as vehicle_name',
                    'T.id as transaction_id',
                    'T.payment_status as t_payment_status',
                    DB::raw('(SELECT SUM(IF(is_return = 1, -1, 1) * amount) FROM transaction_payments WHERE transaction_id = T.id) as total_paid_amount'),
                    DB::raw('(SR.final_total - (SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = SR.id)) as sell_return_due'),
                    DB::raw('(SELECT COUNT(*) FROM distribution_invoice_lines WHERE invoice_id = distribution_invoices.id) as total_items')
                ]);

            // Filters
            if (!empty(request()->invoice_no)) {
                $query->where('distribution_invoices.invoice_no', 'like', '%' . request()->invoice_no . '%');
            }
            if (!empty(request()->customer_id)) {
                $query->where('distribution_invoices.customer_id', request()->customer_id);
            }
            if (!empty(request()->route_id)) {
                $query->where('distribution_invoices.route_id', request()->route_id);
            }
            if (!empty(request()->vehicle_id)) {
                $query->where('distribution_invoices.vehicle_id', request()->vehicle_id);
            }
            if (!empty(request()->added_by)) {
                $query->where('distribution_invoices.added_by', request()->added_by);
            }
            if (!empty(request()->shipping_status)) {
                $query->where('distribution_invoices.shipping_status', request()->shipping_status);
            }
            if (!empty(request()->payment_status)) {
                if (request()->payment_status == 'paid') {
                    $query->whereRaw('(distribution_invoices.grand_total - distribution_invoices.payment_total) <= 0.009');
                } elseif (request()->payment_status == 'due') {
                    $query->whereRaw('distribution_invoices.payment_total = 0');
                } elseif (request()->payment_status == 'partial') {
                    $query->whereRaw('distribution_invoices.payment_total > 0 AND (distribution_invoices.grand_total - distribution_invoices.payment_total) > 0.009');
                }
            }
            if (!empty(request()->payment_method)) {
                $query->where(function($q) {
                    $method = request()->payment_method;
                    $q->where('distribution_invoices.payment_cash', '>', 0)
                      ->whereRaw('? = "cash"', [$method])
                      ->orWhere('distribution_invoices.payment_card', '>', 0)
                      ->whereRaw('? = "card"', [$method])
                      ->orWhere('distribution_invoices.payment_cheque', '>', 0)
                      ->whereRaw('? = "cheque"', [$method]);
                });
                // Note: Simplified logic for payment method filtering as it's stored in separate columns
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $query->whereDate('distribution_invoices.date', '>=', request()->start_date)
                      ->whereDate('distribution_invoices.date', '<=', request()->end_date);
            }

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // View
                    $html .= '<li><a href="' . route('distribution.invoices.show', [$row->id]) . '"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>';

                    // Pay Due Amount
                    $actual_paid = $row->payment_total - $row->payment_credit;
                    $actual_due = $row->grand_total - $actual_paid;
                    if ($actual_due > 0.009 && !empty($row->transaction_id)) {
                        $html .= '<li><a href="' . action("TransactionPaymentController@addPayment", [$row->transaction_id]) . '" class="add_payment_modal"><i class="fa fa-money"></i> ' . __("lang_v1.pay_due_amount") . '</a></li>';
                    }

                    // View Payment
                    if (!empty($row->transaction_id) && $actual_paid > 0) {
                        $html .= '<li><a href="' . action("TransactionPaymentController@show", [$row->transaction_id]) . '" class="view_payment_modal"><i class="fa fa-list"></i> ' . __("lang_v1.view_payment") . '</a></li>';
                    }

                    // Edit
                    if (auth()->user()->can('distribution.edit_invoice')) {
                        $html .= '<li><a href="' . route('distribution.invoices.edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
                    }

                    // Delete
                    if (auth()->user()->can('distribution.delete_invoice')) {
                        $html .= '<li><a href="' . route('distribution.invoices.destroy', [$row->id]) . '" class="delete-dis-invoice"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                    }

                    // Print
                    $html .= '<li><a href="' . route('distribution.invoices.print', [$row->id]) . '" target="_blank"><i class="fa fa-print"></i> ' . __("messages.print") . '</a></li>';

                    // Duplicate
                    $html .= '<li><a href="' . route('distribution.invoices.duplicate', [$row->id]) . '"><i class="fa fa-copy"></i> ' . __("messages.duplicate_invoice") . '</a></li>';

                    // Sell Return
                    if (!empty($row->invoice_no)) {
                        $transaction = \Modules\Distribution\Entities\Core\Transaction::where('invoice_no', $row->invoice_no)->first();
                        if ($transaction) {
                            $html .= '<li><a href="' . url('sell-return/add/' . $transaction->id) . '"><i class="fa fa-undo"></i> ' . __("lang_v1.sell_return") . '</a></li>';
                            $html .= '<li><a href="' . action('SellPosController@showInvoiceUrl', [$transaction->id]) . '" class="view_invoice_url"><i class="fa fa-external-link"></i> ' . __("lang_v1.view_invoice_url") . '</a></li>';
                        }
                    }

                    // Notes (Popup handled by JS)
                    $html .= '<li><a href="#" class="view-notes" data-id="' . $row->id . '"><i class="fa fa-sticky-note"></i> ' . __("brand.note") . '</a></li>';

                    // Changed Activities (Popup handled by JS)
                    $html .= '<li><a href="#" class="view-changed-activities" data-id="' . $row->id . '"><i class="fa fa-history"></i> Activities</a></li>';

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('date', '{{@format_datetime($created_at)}}')
                ->editColumn('delivery_date', '{{@format_date($delivery_date)}}')
                ->addColumn('customer_info', function($row){
                    $name = !empty($row->customer_name) ? $row->customer_name : $row->customer_name_orig;
                    $mobile = !empty($row->customer_contact) ? $row->customer_contact : $row->customer_mobile;
                    return $name . ($mobile ? '<br>' . $mobile : '');
                })
                ->addColumn('total_items', function($row){
                    return '<span class="total_items" data-orig-value="' . $row->total_items . '">' . $row->total_items . '</span>';
                })
                ->editColumn('grand_total', '<span class="display_currency grand_total" data-currency_symbol="true" data-orig-value="{{$grand_total}}">{{$grand_total}}</span>')
                ->editColumn('payment_total', function ($row) {
                    $actual_paid = $row->payment_total - $row->payment_credit;
                    return '<span class="display_currency payment_total" data-currency_symbol="true" data-orig-value="' . $actual_paid . '">' . $actual_paid . '</span>';
                })
                ->addColumn('balance_due', function ($row) {
                    $due = $row->grand_total - ($row->payment_total - $row->payment_credit);
                    return '<span class="display_currency balance_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</span>';
                })
                ->addColumn('sell_return_due', function ($row) {
                    $return_due = $row->sell_return_due ?? 0;
                    return '<span class="display_currency sell_return_due" data-currency_symbol="true" data-orig-value="' . $return_due . '">' . $return_due . '</span>';
                })
                ->editColumn('shipping_status', function($row){
                    $status_colors = [
                        'ordered' => 'btn-aqua',
                        'packed' => 'btn-info',
                        'shipped' => 'btn-warning',
                        'delivered' => 'btn-success',
                        'cancelled' => 'btn-danger'
                    ];
                    $label = ucfirst($row->shipping_status);
                    $color = $status_colors[$row->shipping_status] ?? 'btn-default';
                    
                    return '<button type="button" class="btn ' . $color . ' btn-xs change_shipping_status" data-id="' . $row->id . '" data-status="' . $row->shipping_status . '">' . $label . '</button>';
                })
                ->addColumn('payment_status', function($row){
                    $actual_paid = $row->payment_total - $row->payment_credit;
                    $due = $row->grand_total - $actual_paid;
                    $status = '';
                    if ($due <= 0.009) {
                        $status = '<span class="label bg-green">' . __("lang_v1.paid") . '</span>';
                    } elseif ($actual_paid > 0 && $due > 0.009) {
                        $status = '<span class="label bg-yellow">' . __("lang_v1.partial") . '</span>';
                    } else {
                        $status = '<span class="label bg-red">' . __("lang_v1.due") . '</span>';
                    }
                    
                    return $status;
                })
                ->addColumn('payment_method', function ($row) {
                    $methods = [];
                    if (($row->payment_cash ?? 0) > 0) $methods[] = 'Cash';
                    if (($row->payment_card ?? 0) > 0) $methods[] = 'Card';
                    if (($row->payment_cheque ?? 0) > 0) $methods[] = 'Cheque';
                    if (($row->payment_credit ?? 0) > 0) $methods[] = 'Credit';
                    
                    $html = count($methods) ? implode(', ', $methods) : '-';
                    
                    $actual_paid = $row->payment_total - $row->payment_credit;
                    $due = $row->grand_total - $actual_paid;
                    $is_due = ($actual_paid <= 0 && $due > 0.009);
                    
                    if (!$is_due) {
                        $html .= '<br><button type="button" class="btn btn-xs btn-default show-payment-btn"
                            data-cash="' . number_format(($row->payment_cash ?? 0), 2, '.', '') . '"
                            data-card="' . number_format(($row->payment_card ?? 0), 2, '.', '') . '"
                            data-cheque="' . number_format(($row->payment_cheque ?? 0), 2, '.', '') . '"
                            data-credit="' . number_format(($row->payment_credit ?? 0), 2, '.', '') . '"
                            data-total="' . number_format(($row->payment_total ?? 0), 2, '.', '') . '">
                            Payment Details
                        </button>';
                    }
                    return $html;
                })
                ->rawColumns(['action', 'customer_info', 'grand_total', 'payment_total', 'balance_due', 'sell_return_due', 'shipping_status', 'payment_status', 'total_items', 'payment_method'])
                ->with('footer_data', [
                    'footer_grand_total' => (clone $query)->sum('distribution_invoices.grand_total'),
                    'footer_total_paid' => (clone $query)->sum(DB::raw('distribution_invoices.payment_total - distribution_invoices.payment_credit')),
                    'footer_total_remaining' => (clone $query)->sum(DB::raw('distribution_invoices.grand_total - (distribution_invoices.payment_total - distribution_invoices.payment_credit)')),
                    'footer_total_sell_return_due' => (clone $query)->sum(DB::raw('IFNULL(SR.final_total, 0) - IFNULL((SELECT SUM(amount) FROM transaction_payments WHERE transaction_id = SR.id), 0)')),
                    'footer_total_items' => (clone $query)->sum(DB::raw('(SELECT COUNT(*) FROM distribution_invoice_lines WHERE invoice_id = distribution_invoices.id)'))
                ])
                ->make(true);
        }

        $business = Business::where('id', $business_id)->first();
        $customers = Contact::customersDropdown($business_id, false);
        $routes = Distribution_routes::where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DistributionVehicles::where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $users = User::forDropdown($business_id, false);
        $invoice_numbers = DistributionInvoice::where('business_id', $business_id)->pluck('invoice_no', 'invoice_no');
        
        $payment_methods = [
            'cash' => __('lang_v1.cash'),
            'card' => __('lang_v1.card'),
            'cheque' => __('lang_v1.cheque'),
            'bank_transfer' => __('lang_v1.bank_transfer'),
            'other' => __('lang_v1.other'),
            'custom_pay_1' => __('lang_v1.custom_payment_1'),
            'custom_pay_2' => __('lang_v1.custom_payment_2'),
            'custom_pay_3' => __('lang_v1.custom_payment_3'),
        ];

        return view('distribution::invoices.list_invoices')->with(compact(
            'customers',
            'routes',
            'vehicles',
            'users',
            'invoice_numbers',
            'payment_methods'
        ));
    }

    /**
     * Get notes for an invoice (for popup)
     */
    public function getNotes($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $invoice = DistributionInvoice::where('business_id', $business_id)
            ->with(['salesOrder'])
            ->findOrFail($id);

        return response()->json([
            'invoice_note' => $invoice->invoice_note ?? '',
            'shipping_note' => $invoice->shipping_note ?? '',
            'sales_order_note' => $invoice->sales_order_note ?? (optional($invoice->salesOrder)->invoice_note ?? '')
        ]);
    }

    /**
     * Get activity log for an invoice (for popup)
     */
    public function getActivityLog($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $invoice = DistributionInvoice::where('business_id', $business_id)->findOrFail($id);

        $activities = \Spatie\Activitylog\Models\Activity::forSubject($invoice)
            ->with(['causer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('distribution::invoices.partials.activity_log_popup')->with(compact('activities', 'invoice'));
    }
}
