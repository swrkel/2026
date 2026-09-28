<?php

namespace Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\ContactGroup;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CustomerStandaloneOutstandingController extends Controller
{
    protected $transactionUtil;
    protected $productUtil;

    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $customers = Contact::customersDropdown($business_id, false);
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $payment_types = $this->transactionUtil->payment_types(null, false, false, false, false, true, 'is_sale_enabled');
        $customer_group = ContactGroup::forDropdown($business_id, false, true);
        $types = Contact::typeDropdown(true);
        $payment_pages = $this->transactionUtil->payment_transaction_types;

        return view('customers::contact.outstanding_received_report')->with(compact(
            'suppliers', 'business_locations', 'customers', 'customer_group', 'types', 'payment_types', 'payment_pages'
        ));
    }

    public function filters(Request $request)
    {
        return [
            'payment_ref_nos' => $this->transactionUtil->getOutstandingPaymentRefs($request->start_date, $request->end_date),
            'cheque_numbers' => $this->transactionUtil->getOutstandingCheques($request->start_date, $request->end_date),
        ];
    }

    public function data(Request $request)
    {
        $business_id = (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id);
        $business_details = Business::findOrFail($business_id);

        $outstanding_types = $this->transactionUtil->outstanding_payment_types
            ?? ['sell', 'opening_balance', 'cheque_return'];

        /*
         * Aggregate into a subquery first, then let DataTables search/order the
         * flat result. The previous query selected many non-aggregated columns
         * while grouping only by payment_ref_no, which fails on tenant database
         * connections that use ONLY_FULL_GROUP_BY.
         */
        $paymentGroupExpression = "CASE
            WHEN tp.payment_ref_no IS NOT NULL AND tp.payment_ref_no <> ''
                THEN CONCAT('reference:', tp.payment_ref_no)
            ELSE CONCAT('payment:', tp.id)
        END";

        /*
         |----------------------------------------------------------------------
         | LA-1145: driven from transaction_payments, not from transactions.
         |----------------------------------------------------------------------
         |
         | This used to be Transaction::leftJoin('transaction_payments as tp',
         | 'transactions.id', '=', 'tp.transaction_id')->whereNotNull('tp.id') -
         | i.e. start from invoices and pick up their payments.
         |
         | CustomerPaymentActionService::createTransactionPayment(), behind
         | Customer Register -> Pay due Amount / Advance Payment and the bulk and
         | statement payment paths, never sets transaction_id. Those payments
         | record the customer in `payment_for` and leave transaction_id NULL, so
         | starting from transactions could never reach them however the join was
         | written. That is the reported "payment details are not loading".
         |
         | transactions is now LEFT joined, the contact comes from the transaction
         | when there is one and from payment_for when there is not, and the
         | business and date scoping come from the payment row itself.
         |
         | The transaction-type restriction only applies to rows that HAVE a
         | transaction, so nothing that used to be listed is lost.
         */
        $groupedPayments = DB::table('transaction_payments as tp')
            ->leftJoin('transactions', 'transactions.id', '=', 'tp.transaction_id')
            ->leftJoin('contacts', function ($join) {
                $join->on('contacts.id', '=', DB::raw('COALESCE(transactions.contact_id, tp.payment_for)'));
            })
            ->leftJoin('users as user', 'tp.created_by', '=', 'user.id')
            ->whereNull('tp.deleted_at')
            ->where('tp.business_id', $business_id)
            ->where('contacts.type', 'customer')
            ->where(function ($query) use ($outstanding_types) {
                $query->whereNull('tp.transaction_id')
                    ->orWhere(function ($invoiceLinked) use ($outstanding_types) {
                        $invoiceLinked->whereIn('transactions.type', $outstanding_types)
                            ->where('transactions.type', '!=', 'settlement');
                    });
            });

        if ($request->filled('customer_id')) {
            $groupedPayments->where('contacts.id', $request->customer_id);
        }
        if ($request->filled('bill_no')) {
            $groupedPayments->where('transactions.invoice_no', $request->bill_no);
        }
        if ($request->filled('payment_ref_no')) {
            $groupedPayments->where('tp.payment_ref_no', $request->payment_ref_no);
        }
        if ($request->filled('cheque_number')) {
            $groupedPayments->where('tp.cheque_number', $request->cheque_number);
        }
        if ($request->filled('payment_type')) {
            $groupedPayments->where('tp.method', $request->payment_type);
        }
        if ($request->filled('payment_page')) {
            $groupedPayments->where('transactions.type', $request->payment_page);
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $groupedPayments->whereDate('tp.paid_on', '>=', $request->start_date)
                ->whereDate('tp.paid_on', '<=', $request->end_date);
        }

        $groupedPayments
            ->selectRaw('MIN(transactions.id) as id')
            ->selectRaw('MAX(transactions.transaction_date) as transaction_date')
            ->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(transactions.invoice_no, '') ORDER BY NULLIF(transactions.invoice_no, '') SEPARATOR ', ') as invoice_no")
            ->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(tp.payment_ref_no, '') SEPARATOR ', ') as ref_nos")
            ->selectRaw('MAX(contacts.name) as name')
            ->selectRaw('MAX(transactions.payment_status) as payment_status')
            ->selectRaw('MAX(transactions.final_total) as final_total')
            ->selectRaw('MAX(tp.id) as tp_id')
            ->selectRaw('MAX(tp.paid_on) as paid_on')
            ->selectRaw('MAX(tp.paid_in_type) as paid_in_type')
            ->selectRaw('MAX(tp.method) as method')
            ->selectRaw('MAX(tp.parent_id) as parent_id')
            ->selectRaw('MAX(tp.cheque_number) as cheque_number')
            ->selectRaw('MAX(tp.card_number) as card_number')
            ->selectRaw('MAX(tp.bank_name) as bank_name')
            ->selectRaw('MAX(tp.payment_ref_no) as payment_ref_no')
            ->selectRaw('MAX(tp.created_at) as created_at')
            ->selectRaw('MAX(tp.linked_customer_statement) as linked_customer_statement')
            /*
             * LA-1145: a payment against the customer's balance has no
             * transaction, so transactions.type is NULL and the Payment Type
             * column would render blank - which reads as a fault. Label it for
             * what it is instead.
             */
            ->selectRaw("MAX(COALESCE(transactions.type, 'customer_payment')) as ttype")
            ->selectRaw("MAX(CONCAT_WS(' ', user.first_name, user.last_name)) as user_name")
            ->selectRaw('SUM(tp.amount) as total_paid')
            ->selectRaw($paymentGroupExpression . ' as payment_group_key')
            ->groupBy(DB::raw($paymentGroupExpression));

        $query = DB::query()
            ->fromSub($groupedPayments, 'payments')
            ->select('payments.*')
            ->orderByDesc('payments.paid_on');

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $view = route('customers.customer_payment.view', [$row->tp_id]);
                $edit = route('customers.customer_payments.edit', [$row->tp_id]);

                return '<div class="btn-group"><button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">'
                    . __('messages.actions')
                    . ' <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right">'
                    . '<li><a href="#" data-href="' . e($view) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-external-link"></i> ' . __('messages.view') . '</a></li>'
                    . '<li><a href="#" data-href="' . e($edit) . '" class="btn-modal-edit" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>'
                    . '</ul></div>';
            })
            ->editColumn('payment_ref_no', function ($row) {
                $reference = (string) ($row->payment_ref_no ?? '');
                $view = route('customers.customer_payment.view', [$row->tp_id]);

                return '<a href="#" data-href="' . e($view) . '" class="btn-modal" data-container=".view_modal">' . e($reference) . '</a>';
            })
            ->addColumn('paid_for', function ($row) {
                // The payment rows are allocated to the invoices collected in this group.
                return e((string) ($row->invoice_no ?? ''));
            })
            ->addColumn('payment_amount', function ($row) use ($business_details) {
                return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . e($row->total_paid) . '">'
                    . $this->productUtil->num_f($row->total_paid, false, $business_details, false)
                    . '</span>';
            })
            ->editColumn('total_paid', function ($row) use ($business_details) {
                return '<span class="display_currency total-paid" data-currency_symbol="true" data-orig-value="' . e($row->total_paid) . '">'
                    . $this->productUtil->num_f($row->total_paid, false, $business_details, false)
                    . '</span>';
            })
            ->editColumn('final_total', function ($row) use ($business_details) {
                return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . e($row->final_total) . '">'
                    . $this->productUtil->num_f($row->final_total, false, $business_details, false)
                    . '</span>';
            })
            ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')
            ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
            ->rawColumns(['action', 'payment_ref_no', 'payment_amount', 'total_paid', 'final_total'])
            ->make(true);
    }

}
