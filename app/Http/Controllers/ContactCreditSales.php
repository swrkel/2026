<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\Settlement;
use Yajra\DataTables\Facades\DataTables;

class ContactCreditSales extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {

        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
    }

    public function index(): mixed
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $report_type = request()->input('report_type');

            if ($report_type == 'card') {
                $start_date = request()->input('start_date');
                $end_date = request()->input('end_date');
                $invoice_no = request()->input('invoice_no');
                $location_id = request()->input('location_id');
                $card_type = request()->input('card_type');
                $slip_no = request()->input('slip_no');
                $customer_id = request()->input('customer_id');

                $transactions = Transaction::where('transactions.business_id', $business_id)
                    ->leftJoin('business_locations as bl', 'bl.id', 'transactions.location_id')
                    ->join('transaction_payments', function ($join) use ($report_type) {
                        $join->on('transaction_payments.transaction_id', '=', 'transactions.id')
                            ->where('transaction_payments.method', 'card');
                    })
                    ->where('transactions.type', 'sell')
                    ->leftJoin('accounts as card_accounts', 'transaction_payments.card_type', '=', 'card_accounts.id')
                    ->select([
                        'transactions.transaction_date',
                        'bl.name as location_name',
                        'transaction_payments.method as payment_method',
                        'transactions.invoice_no',
                        'card_accounts.name as card_type',
                        'transaction_payments.card_number as slip_no',
                        'transaction_payments.amount',
                    ]);

                if (!empty($start_date) && !empty($end_date)) {
                    $transactions->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                }
                if (!empty($invoice_no)) {
                    $transactions->where('transactions.invoice_no', $invoice_no);
                }
                if (!empty($location_id)) {
                    $transactions->where('transactions.location_id', $location_id);
                }
                if (!empty($card_type)) {
                    $transactions->where('transaction_payments.card_type', $card_type);
                }
                if (!empty($slip_no)) {
                    $transactions->where('transaction_payments.card_number', $slip_no);
                }
                if (!empty($customer_id)) {
                    $transactions->where('transactions.contact_id', $customer_id);
                }

                return DataTables::of($transactions)
                    ->editColumn('slip_no', function ($row) {
                        return $row->slip_no ?: 'N/A';
                    })
                    ->editColumn('location_name', function ($row) {
                        return $row->location_name ?: 'N/A';
                    })
                    ->editColumn('card_type', function ($row) {
                        return $row->card_type ?: 'N/A';
                    })
                    ->editColumn('amount', '<span data-orig-value="{{$amount}}" class="final-total">{{@num_format($amount)}}</span>')
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->rawColumns(['amount'])
                    ->make(true);
            } else if ($report_type == 'cash') {
                $start_date = request()->input('start_date');
                $end_date = request()->input('end_date');
                $invoice_no = request()->input('invoice_no');
                $location_id = request()->input('location_id');
                $customer_id = request()->input('customer_id');

                $transactions = Transaction::where('transactions.business_id', $business_id)
                    ->leftJoin('business_locations as bl', 'bl.id', 'transactions.location_id')
                    ->leftJoin('transaction_payments', function ($join) {
                        $join->on('transaction_payments.transaction_id', '=', 'transactions.id')
                            ->where('transaction_payments.method', 'card');
                    })
                    ->leftJoin('accounts as card_accounts', 'transaction_payments.card_type', '=', 'card_accounts.id')
                    ->where('transactions.type', 'sell')
                    ->select([
                        'transactions.transaction_date',
                        'bl.name as location_name',
                        'transaction_payments.method as payment_method',
                        'transactions.invoice_no',
                        'card_accounts.name as card_type',
                        'transaction_payments.card_number as slip_no',
                        'transaction_payments.amount',
                    ]);

                if (!empty($start_date) && !empty($end_date)) {
                    $transactions->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                }
                if (!empty($invoice_no)) {
                    $transactions->where('transactions.invoice_no', $invoice_no);
                }
                if (!empty($location_id)) {
                    $transactions->where('transactions.location_id', $location_id);
                }

                if (!empty($customer_id)) {
                    $transactions->where('transactions.contact_id', $customer_id);
                }

                return DataTables::of($transactions)
                    ->editColumn('location_name', function ($row) {
                        return $row->location_name ?: 'N/A';
                    })
                    ->editColumn('amount', '<span data-orig-value="{{$amount}}" class="final-total">{{@num_format($amount)}}</span>')
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->rawColumns(['amount'])
                    ->make(true);
            } else if ($report_type == 'credit') {
                $customer_id = request()->input('customer_id');
                $start_date = request()->input('start_date');
                $end_date = request()->input('end_date');
                $invoice_no = request()->input('invoice_no');
                $location_id = request()->input('location_id');

                $transactions = Transaction::where('transactions.business_id', $business_id)
                    ->leftJoin('business_locations as bl', 'bl.id', 'transactions.location_id')
                    ->leftJoin('transaction_payments', function ($join) {
                        $join->on('transaction_payments.transaction_id', '=', 'transactions.id')
                            ->where('transaction_payments.method', 'credit_sale');
                    })
                    ->where('transactions.type', 'sell')
                    ->where(function ($q) {
                        $q->whereNotNull('transaction_payments.id')
                            ->orWhere(function ($sub) {
                                $sub->whereNull('transaction_payments.id')
                                    ->where(function ($sub2) {
                                        $sub2->where('transactions.payment_status', 'due')
                                            ->orWhere('transactions.sub_type', 'credit_sale')
                                            ->orWhere('transactions.is_credit_sale', 1);
                                    });
                            });
                    })
                    ->select([
                        'transactions.transaction_date',
                        'bl.name as location_name',
                        'transactions.invoice_no',
                        DB::raw('
            COALESCE(transaction_payments.amount, transactions.final_total) 
            as amount
        '),
                    ]);

                if (!empty($start_date) && !empty($end_date)) {
                    $transactions->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                }
                if (!empty($invoice_no)) {
                    $transactions->where('transactions.invoice_no', $invoice_no);
                }
                if (!empty($location_id)) {
                    $transactions->where('transactions.location_id', $location_id);
                }

                if (!empty($customer_id)) {
                    $transactions->where('transactions.contact_id', $customer_id);
                }

                return DataTables::of($transactions)
                    ->editColumn('location_name', function ($row) {
                        return $row->location_name ?: 'N/A';
                    })
                    ->editColumn('amount', '<span data-orig-value="{{$amount}}" class="final-total">{{@num_format($amount)}}</span>')
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->rawColumns(['amount'])
                    ->make(true);
            } else {
                $customer_id = request()->input('customer_id');
                $invoice_no = request()->input('invoice_no');
                $location_id = request()->input('location_id');
                $start_date = request()->input('start_date');
                $end_date = request()->input('end_date');

                $transactions = Transaction::where('transactions.business_id', $business_id)
                    ->leftJoin('business_locations as bl', 'bl.id', 'transactions.location_id')
                    ->join('transaction_payments', 'transaction_payments.transaction_id', '=', 'transactions.id')
                    ->where('transactions.type', 'sell')
                    ->select([
                        'transactions.transaction_date',
                        'bl.name as location_name',
                        'transactions.invoice_no',
                        'transaction_payments.amount',
                        'transaction_payments.method as payment_method',
                        'transactions.payment_status'
                    ]);

                if (!empty($start_date) && !empty($end_date)) {
                    $transactions->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
                }

                if (!empty($invoice_no)) {
                    $transactions->where('transactions.invoice_no', $invoice_no);
                }

                if (!empty($location_id)) {
                    $transactions->where('transactions.location_id', $location_id);
                }

                if (!empty($customer_id)) {
                    $transactions->where('transactions.contact_id', $customer_id);
                }

                return DataTables::of($transactions)
                    ->editColumn('customer_name', function ($row) {
                        if ($row->sub_type == 'shortage' || $row->sub_type == 'excess') {
                            $settlement = Settlement::where('settlement_no', $row->invoice_no)->first();
                            if (!empty($settlement)) {
                                $operator = PumpOperator::find($settlement->pump_operator_id);
                                return __('contact_credit_sales.pumper') . " " . (!empty($operator) ? $operator->name : "") .
                                    " " . __('contact_credit_sales.' . $row->sub_type);
                            }
                        }
                        return $row->customer_name;
                    })
                    ->editColumn('payment_status', function ($row) {
                        $payment_status = Transaction::getPaymentStatus($row);
                        return (string)view('sell.partials.payment_status', [
                            'payment_status' => $payment_status,
                            'id' => $row->id,
                        ]);
                    })
                    ->editColumn('amount', '<span data-orig-value="{{$amount}}" class="final-total">{{@num_format($amount)}}</span>')
                    ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                    ->rawColumns(['amount', 'payment_status'])
                    ->make(true);
            }
        }

        $customers = Contact::customersDropdown($business_id, false);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $q1 = Transaction::where('is_credit_sale', 1)
            ->where('business_id', $business_id)
            ->select('invoice_no');
        $q2 = TransactionPayment::join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transaction_payments.method', 'credit_sale')
            ->where('transactions.business_id', $business_id)
            ->select('transactions.invoice_no');
        $invoiceCollection = $q1->union($q2)->get()->unique('invoice_no');
        $invoices = $invoiceCollection->pluck('invoice_no', 'invoice_no');

        // Get card types from transaction_payments joined with accounts
        $card_types = DB::table('transaction_payments')
            ->where('transaction_payments.business_id', $business_id)
            ->where('transaction_payments.method', 'card')
            ->whereNotNull('transaction_payments.card_type')
            ->join('accounts', 'transaction_payments.card_type', '=', 'accounts.id')
            ->distinct()
            ->pluck('accounts.name', 'accounts.id');

        // If no card types found via join, try to find accounts that might be card types
        if ($card_types->count() == 0) {
            $card_types = DB::table('accounts')
                ->where('business_id', $business_id)
                ->where(function ($query) {
                    $query->where('name', 'like', '%card%')
                        ->orWhere('name', 'like', '%credit%')
                        ->orWhere('name', 'like', '%debit%')
                        ->orWhere('name', 'like', '%visa%')
                        ->orWhere('name', 'like', '%master%');
                })
                ->pluck('name', 'id');
        }

        // Get slip numbers - use cheque_number since card_number is mostly null in your data
        $slip_numbers = DB::table('transaction_payments')
            ->where('business_id', $business_id)
            ->where('method', 'card')
            ->whereNotNull('cheque_number')
            ->where('cheque_number', '!=', '')
            ->distinct()
            ->pluck('cheque_number', 'cheque_number');

        // If no cheque numbers found, try using card_number as fallback
        if ($slip_numbers->count() == 0) {
            $slip_numbers = DB::table('transaction_payments')
                ->where('business_id', $business_id)
                ->where('method', 'card')
                ->whereNotNull('card_number')
                ->where('card_number', '!=', '')
                ->distinct()
                ->pluck('card_number', 'card_number');
        }

        // Final fallback - if still no slip numbers, use payment IDs
        if ($slip_numbers->count() == 0) {
            $slip_numbers = DB::table('transaction_payments')
                ->where('business_id', $business_id)
                ->where('method', 'card')
                ->distinct()
                ->pluck('id', 'id')
                ->mapWithKeys(function ($id) {
                    return [$id => 'PAY-' . $id];
                });
        }

        // Log final results for debugging
        \Log::info('Final Card Types Count:', [$card_types->count()]);
        \Log::info('Final Slip Numbers Count:', [$slip_numbers->count()]);

        return view('contact_credit_sales.index')
            ->with(compact('invoices', 'customers', 'business_locations', 'card_types', 'slip_numbers'));
    }

}
