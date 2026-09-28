<?php
namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\ContactLedger;
use App\PurchaseLine;
use App\Store;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReturnController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;
    protected $productUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil     = $productUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $purchases_returns = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->join(
                    'business_locations AS BS',
                    'transactions.location_id',
                    '=',
                    'BS.id'
                )
                ->leftJoin(
                    'transactions AS T',
                    'transactions.return_parent_id',
                    '=',
                    'T.id'
                )
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase_return')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'contacts.name',
                    'transactions.status',
                    'transactions.payment_status',
                    'transactions.final_total',
                    'transactions.return_parent_id',
                    'BS.name as location_name',
                    'T.ref_no as parent_purchase',
                    DB::raw('SUM(TP.amount) as amount_paid')
                )
                ->groupBy('transactions.id');

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $purchases_returns->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! empty(request()->supplier_id)) {
                $supplier_id = request()->supplier_id;
                $purchases_returns->where('contacts.id', $supplier_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $start = request()->start_date;
                $end   = request()->end_date;
                $purchases_returns->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }
            return Datatables::of($purchases_returns)
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (! empty($row->return_parent_id)) {
                        $html .= '<a href="' . action('PurchaseReturnController@add', $row->return_parent_id) . '" class="btn btn-info btn-xs" ><i class="glyphicon glyphicon-edit"></i>' .
                        __("messages.edit") .
                            '</a>';
                    } else {
                        $html .= '<a href="' . action('CombinedPurchaseReturnController@edit', $row->id) . '" class="btn btn-info btn-xs" ><i class="glyphicon glyphicon-edit"></i>' .
                        __("messages.edit") .
                            '</a>';
                    }

                    $html .= '<a href="' . action('PurchaseReturnController@destroy', $row->id) . '" class="btn btn-danger btn-xs delete_purchase_return" ><i class="fa fa-trash"></i>' .
                    __("messages.delete") .
                        '</a>';

                    return $html;
                })
                ->removeColumn('id')
                ->removeColumn('return_parent_id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')

                ->editColumn(
                    'payment_status',
                    '<a href="{{ action("TransactionPaymentController@show", [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}}@else{{__("lang_v1.received")}}@endif"><span class="label @payment_status($payment_status)">@if($payment_status != "paid"){{__(\'lang_v1.\' . $payment_status)}} @else {{__("lang_v1.received")}} @endif
                        </span></a>'
                )
                ->editColumn('parent_purchase', function ($row) {
                    $html = '';
                    if (! empty($row->parent_purchase)) {
                        $html = '<a href="#" data-href="' . action('PurchaseController@show', [$row->return_parent_id]) . '" class="btn-modal" data-container=".view_modal">' . $row->parent_purchase . '</a>';
                    }
                    return $html;
                })
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;
                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can("purchase.view")) {
                            $return_id = ! empty($row->return_parent_id) ? $row->return_parent_id : $row->id;
                            return action('PurchaseReturnController@show', [$return_id]);
                        } else {
                            return '';
                        }
                    },
                ])
                ->rawColumns(['final_total', 'action', 'payment_status', 'parent_purchase', 'payment_due'])
                ->make(true);
        }
        return view('purchase_return.index');
    }

    /**
     * Show the form for purchase return.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        Log::info('purchase return controller add');
        if (! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->with(['purchase_lines', 'contact', 'tax', 'return_parent', 'purchase_lines.sub_unit', 'purchase_lines.product', 'purchase_lines.product.unit'])
            ->find($id);

        foreach ($purchase->purchase_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_purchase_line         = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->purchase_lines[$key] = $formated_purchase_line;
            }
        }

        foreach ($purchase->purchase_lines as $key => $value) {
            $qty_available = $value->quantity - $value->quantity_sold - $value->quantity_adjusted;

            $purchase->purchase_lines[$key]->formatted_qty_available = $this->transactionUtil->num_f($qty_available);
        }

        $purchase_return_accounts = \App\Models\PurchaseReturnAccount::where('business_id', $business_id)
            ->with('account')
            ->get()
            ->pluck('account.name', 'id');

        $existing_return_transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase_return')
            ->where('return_parent_id', $purchase->id)
            ->first();

        $purchase_return_entry_details = $this->getExistingPurchaseReturnEntryDetails(
            ! empty($existing_return_transaction) ? $existing_return_transaction->id : null,
            $business_id
        );

        return view('purchase_return.add')
            ->with(compact('purchase', 'purchase_return_accounts', 'purchase_return_entry_details'));
    }

    /**
     * Saves Purchase returns in the database.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Log::info('in store');
        if (! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->with(['purchase_lines', 'purchase_lines.sub_unit'])
                ->findOrFail($request->input('transaction_id'));

            $has_reviewed = $this->transactionUtil->hasReviewed($purchase->transaction_date);

            if (! empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($purchase->transaction_date, $purchase->transaction_date);

            if (! empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't edit a purchase for an already reviewed date",
                ];

                return redirect('purchase-return')->with('status', $output);
            }

            // Get purchase return account details
            $purchase_return_account_id = $request->input('purchase_return_account_id');
            $bank_name                  = $request->input('bank_name');
            $cheque_number              = $request->input('cheque_number');
            $cheque_date                = $request->input('cheque_date');

            // Validate purchase return account
            if (empty($purchase_return_account_id)) {
                throw new \Exception("Purchase return account is required");
            }

            DB::beginTransaction();

            $return_quantities    = $request->input('returns');
            $return_total         = 0;
            $return_lines_payload = [];


            $store_id = app(\App\Services\StoreStockIntegrityService::class)
                ->resolveStoreIdForLocation(
                    (int) $purchase->location_id,
                    !empty($purchase->store_id) ? (int) $purchase->store_id : null,
                    (int) $business_id
                );

            foreach ($purchase->purchase_lines as $purchase_line) {
                $old_return_qty = $purchase_line->quantity_returned ?? 0;

                $raw_return      = $return_quantities[$purchase_line->id] ?? 0;
                $return_quantity = $this->productUtil->num_uf($raw_return);

                $multiplier     = $purchase_line->sub_unit->base_unit_multiplier ?? 1;
                $new_return_qty = $return_quantity * $multiplier;

                if ($new_return_qty < 0) {
                    $new_return_qty = 0;
                }

                $adjustment_type = null;
                if ($new_return_qty > $old_return_qty) {
                    $adjustment_type = 'decrease';
                } elseif ($new_return_qty < $old_return_qty) {
                    $adjustment_type = 'increase';
                }

                if (! is_null($adjustment_type)) {
                    $this->productUtil->decreaseProductQuantity(
                        $purchase_line->product_id,
                        $purchase_line->variation_id,
                        $purchase->location_id,
                        $new_return_qty,
                        $old_return_qty,
                        $adjustment_type
                    );

                    if (! empty($store_id)) {
                        $this->productUtil->decreaseProductQuantityStore(
                            $purchase_line->product_id,
                            $purchase_line->variation_id,
                            $purchase->location_id,
                            $new_return_qty,
                            $store_id,
                            $adjustment_type,
                            $old_return_qty
                        );
                    }
                }

                $purchase_line->quantity_returned = $new_return_qty;
                $purchase_line->save();

                $return_total += $purchase_line->purchase_price_inc_tax * $purchase_line->quantity_returned;

                if ($new_return_qty > 0) {
                    $return_lines_payload[] = [
                        'product_id'             => $purchase_line->product_id,
                        'variation_id'           => $purchase_line->variation_id,
                        'quantity'               => 0,
                        'quantity_returned'      => $new_return_qty,
                        'purchase_price'         => $purchase_line->purchase_price,
                        'pp_without_discount'    => $purchase_line->pp_without_discount,
                        'purchase_price_inc_tax' => $purchase_line->purchase_price_inc_tax,
                        'unit_id'                => $purchase_line->unit_id,
                        'sub_unit_id'            => $purchase_line->sub_unit_id,
                        'lot_number'             => $purchase_line->lot_number,
                        'exp_date'               => $purchase_line->exp_date,
                    ];
                }
            }
            $tax_amount           = $this->productUtil->num_uf($request->input('tax_amount', 0));
            $return_total_inc_tax = $return_total + $tax_amount;

            $return_transaction_data = [
                'total_before_tax' => $return_total,
                'final_total'      => $return_total_inc_tax,
                'tax_amount'       => $tax_amount,
                'tax_id'           => $purchase->tax_id,
            ];

            if (empty($request->input('ref_no'))) {
                //Update reference count
                $ref_count                         = $this->transactionUtil->setAndGetReferenceCount('purchase_return');
                $return_transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('purchase_return', $ref_count);
            } else {
                $return_transaction_data['ref_no'] = $request->input('ref_no');
            }

            $return_transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase_return')
                ->where('return_parent_id', $purchase->id)
                ->first();

            if (! empty($return_transaction)) {
                $return_transaction->update($return_transaction_data);
                PurchaseLine::where('transaction_id', $return_transaction->id)->delete();
            } else {
                $return_transaction_data['business_id']      = $business_id;
                $return_transaction_data['location_id']      = $purchase->location_id;
                $return_transaction_data['type']             = 'purchase_return';
                $return_transaction_data['status']           = 'final';
                $return_transaction_data['contact_id']       = $purchase->contact_id;
                $return_transaction_data['transaction_date'] = \Carbon::now();
                $return_transaction_data['created_by']       = request()->session()->get('user.id');
                $return_transaction_data['return_parent_id'] = $purchase->id;

                $return_transaction = Transaction::create($return_transaction_data);
            }

            foreach ($return_lines_payload as $payload) {
                $payload['transaction_id'] = $return_transaction->id;
                PurchaseLine::create($payload);
            }

            //update payment status
            $this->transactionUtil->updatePaymentStatus($return_transaction->id, $return_transaction->final_total);

            // clear any previous manual account transactions (not those created via payments)
            AccountTransaction::where('transaction_id', $return_transaction->id)->delete();

            // also remove any previous transaction payment records (old implementation)
            \App\TransactionPayment::where('transaction_id', $return_transaction->id)->delete();

            ContactLedger::where('transaction_id', $return_transaction->id)->delete();

            // Create purchase return accounting entries.
            $this->createPurchaseReturnAccountingEntries(
                $return_transaction,
                $purchase_return_account_id,
                $bank_name,
                $cheque_number,
                $cheque_date
            );

            // Update supplier ledger with purchase return account details
            $this->updateSupplierLedger(
                $return_transaction,
                $purchase_return_account_id,
                $bank_name,
                $cheque_number,
                $cheque_date
            );

            DB::commit();

            return redirect('purchase-return')->with('status', [
                'success' => 1,
                'msg'     => __('lang_v1.purchase_return_added_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => 0,
                'msg'     => $e->getMessage(), // Show actual error for debugging
            ];
        }

        return redirect('purchase-return')->with('status', $output);
    }

/**
 * Create accounting entries for purchase return
 */
    private function createPurchaseReturnAccountingEntries($purchase_return, $purchase_return_account_id, $bank_name, $cheque_number, $cheque_date)
    {
        $business_id = request()->session()->get('user.business_id');

        // Rebuild manual purchase return postings from scratch so Finished Goods
        // only keeps the intended single credit-side entry for this transaction.
        \App\AccountTransaction::where('transaction_id', $purchase_return->id)
            ->whereNull('transaction_payment_id')
            ->delete();

        $finished_goods_account = $this->getFinishedGoodsAccount($business_id);

        // Get selected purchase return account
        $purchase_return_account = \App\Models\PurchaseReturnAccount::with('account')
            ->where('business_id', $business_id)
            ->find($purchase_return_account_id);

        if (! $purchase_return_account) {
            throw new \Exception("Selected purchase return account not found");
        }

        // Get supplier details
        $supplier      = \App\Contact::find($purchase_return->contact_id);
        $supplier_name = $supplier ? $supplier->name : 'Unknown Supplier';

        $purchase_return_account_name = $purchase_return_account->account->name ?? 'N/A';

        $finished_goods_note = "Supplier: {$supplier_name}\n"
            . "Purchase Return No: {$purchase_return->ref_no}\n"
            . "Purchase Return Account: {$purchase_return_account_name}";

        $purchase_return_account_note = $finished_goods_note;

        // 1. Debit Entry - Purchase Return Account
        $debit_data = [
            'transaction_id' => $purchase_return->id,
            'business_id'    => $business_id,
            'amount'         => $purchase_return->final_total,
            'account_id'     => $purchase_return_account->account_id,
            'type'           => 'debit',
            'operation_date' => $purchase_return->transaction_date,
            'created_by'     => request()->session()->get('user.id'),
            'note'           => $purchase_return_account_note,
            'cheque_number'  => $cheque_number,
            'bank_name'      => $bank_name,
            'cheque_date'    => $cheque_date,
        ];

        // 2. Credit Entry - Finished Goods Account
        $credit_data = [
            'transaction_id' => $purchase_return->id,
            'business_id'    => $business_id,
            'amount'         => $purchase_return->final_total,
            'account_id'     => $finished_goods_account->id,
            'type'           => 'credit',
            'operation_date' => $purchase_return->transaction_date,
            'created_by'     => request()->session()->get('user.id'),
            'note'           => $finished_goods_note,
            'cheque_number'  => $cheque_number,
            'bank_name'      => $bank_name,
            'cheque_date'    => $cheque_date,
        ];

        AccountTransaction::createAccountTransaction($debit_data);
        AccountTransaction::createAccountTransaction($credit_data);
    }

/**
 * Update supplier ledger with purchase return account details
 */
    private function updateSupplierLedger($purchase_return, $purchase_return_account_id, $bank_name, $cheque_number, $cheque_date)
    {
        $business_id = request()->session()->get('user.business_id');

        // Get purchase return account details
        $purchase_return_account = \App\Models\PurchaseReturnAccount::with('account')
            ->where('business_id', $business_id)
            ->find($purchase_return_account_id);

        if ($purchase_return_account) {
            $payment_details = "Purchase Return Account: {$purchase_return_account->account->name}";

            if (! empty($cheque_number)) {
                $payment_details .= " | Cheque: {$cheque_number}";
                if (! empty($bank_name)) {
                    $payment_details .= " - {$bank_name}";
                }
            }

            $contact_ledger_data = [
                'contact_id'     => $purchase_return->contact_id,
                'type'           => 'purchase_return',
                'amount'         => $purchase_return->final_total,
                'operation_date' => $purchase_return->transaction_date,
                'created_by'     => request()->session()->get('user.id'),
                'transaction_id' => $purchase_return->id,
                'note'           => $payment_details,
            ];

            \App\ContactLedger::createContactLedger($contact_ledger_data);
        }
    }

    private function getFinishedGoodsAccount($business_id)
    {
        $finished_goods_account_id = $this->transactionUtil->account_exist_return_id('Finished Goods Account');

        if (! empty($finished_goods_account_id)) {
            $finished_goods_account = \App\Account::where('business_id', $business_id)
                ->find($finished_goods_account_id);

            if (! empty($finished_goods_account)) {
                return $finished_goods_account;
            }
        }

        $finished_goods_account = \App\Account::where('business_id', $business_id)
            ->where(function ($query) {
                $query->where('name', 'like', '%Finished Goods%')
                    ->orWhere('name', 'like', '%Finished goods%')
                    ->orWhere('name', 'like', '%finished goods%');
            })
            ->first();

        if (! empty($finished_goods_account)) {
            return $finished_goods_account;
        }

        $inventory_account = \App\Account::where('business_id', $business_id)
            ->where(function ($query) {
                $query->where('name', 'like', '%Inventory%')
                    ->orWhere('name', 'like', '%inventory%');
            })
            ->first();

        if (empty($inventory_account)) {
            throw new \Exception("Finished Goods/Inventory account not found. Please configure your chart of accounts.");
        }

        return $inventory_account;
    }

    private function getExistingPurchaseReturnEntryDetails($transaction_id, $business_id)
    {
        $details = [
            'purchase_return_account_id' => null,
            'bank_name' => null,
            'cheque_number' => null,
            'cheque_date' => null,
        ];

        if (empty($transaction_id)) {
            return $details;
        }

        $purchase_return_account_ids = \App\Models\PurchaseReturnAccount::where('business_id', $business_id)
            ->pluck('id', 'account_id');

        $debit_entry = \App\AccountTransaction::where('transaction_id', $transaction_id)
            ->where('type', 'debit')
            ->orderByDesc('id')
            ->first();

        if (! empty($debit_entry)) {
            $details['purchase_return_account_id'] = $purchase_return_account_ids[$debit_entry->account_id] ?? null;
            $details['bank_name'] = $debit_entry->bank_name ?: null;
            $details['cheque_number'] = $debit_entry->cheque_number ?: null;
            $details['cheque_date'] = $debit_entry->cheque_date ?: null;
        }

        return $details;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (! auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $purchase = Transaction::where('business_id', $business_id)
            ->with(['return_parent', 'return_parent.tax', 'purchase_lines', 'contact', 'tax', 'purchase_lines.sub_unit', 'purchase_lines.product', 'purchase_lines.product.unit'])
            ->find($id);

        foreach ($purchase->purchase_lines as $key => $value) {
            if (! empty($value->sub_unit_id)) {
                $formated_purchase_line         = $this->productUtil->changePurchaseLineUnit($value, $business_id);
                $purchase->purchase_lines[$key] = $formated_purchase_line;
            }
        }

        $purchase_taxes = [];
        if (! empty($purchase->return_parent->tax)) {
            if ($purchase->return_parent->tax->is_tax_group) {
                $purchase_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($purchase->return_parent->tax, $purchase->return_parent->tax_amount));
            } else {
                $purchase_taxes[$purchase->return_parent->tax->name] = $purchase->return_parent->tax_amount;
            }
        }

        //For combined purchase return return_parent is empty
        if (empty($purchase->return_parent) && ! empty($purchase->tax)) {
            if ($purchase->tax->is_tax_group) {
                $purchase_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($purchase->tax, $purchase->tax_amount));
            } else {
                $purchase_taxes[$purchase->tax->name] = $purchase->tax_amount;
            }
        }

        return view('purchase_return.show')
            ->with(compact('purchase', 'purchase_taxes'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! auth()->user()->can('purchase.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            if (request()->ajax()) {
                $business_id = request()->session()->get('user.business_id');

                $purchase_return = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'purchase_return')
                    ->with(['purchase_lines'])
                    ->first();

                $has_reviewed = $this->transactionUtil->hasReviewed($purchase_return->transaction_date);

                if (! empty($has_reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => __('lang_v1.review_first'),
                    ];

                    return redirect()->back()->with(['status' => $output]);
                }

                $reviewed = $this->transactionUtil->get_review($purchase_return->transaction_date, $purchase_return->transaction_date);

                if (! empty($reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => "You can't delete a return for an already reviewed date",
                    ];

                    return $output;
                }

                DB::beginTransaction();

                if (empty($purchase_return->return_parent_id)) {
                    $delete_purchase_lines    = $purchase_return->purchase_lines;
                    $delete_purchase_line_ids = [];
                    foreach ($delete_purchase_lines as $purchase_line) {
                        $delete_purchase_line_ids[] = $purchase_line->id;
                        $this->productUtil->updateProductQuantity($purchase_return->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned, 0, null, false);
                        $this->productUtil->updateProductQuantityStore($purchase_return->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned);
                    }
                    PurchaseLine::where('transaction_id', $purchase_return->id)
                        ->whereIn('id', $delete_purchase_line_ids)
                        ->delete();
                } else {
                    $parent_purchase = Transaction::where('id', $purchase_return->return_parent_id)
                        ->where('business_id', $business_id)
                        ->where('type', 'purchase')
                        ->with(['purchase_lines'])
                        ->first();

                    $updated_purchase_lines = $parent_purchase->purchase_lines;
                    foreach ($updated_purchase_lines as $purchase_line) {
                        $this->productUtil->updateProductQuantity($parent_purchase->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned, 0, null, false);
                        $this->productUtil->updateProductQuantityStore($parent_purchase->location_id, $purchase_line->product_id, $purchase_line->variation_id, $purchase_line->quantity_returned);

                        $purchase_line->quantity_returned = 0;
                        $purchase_line->save();
                    }
                }

                //Delete Transaction
                $purchase_return->delete();

                //Delete account transactions
                AccountTransaction::where('transaction_id', $id)->delete();

                DB::commit();

                $output = [
                    'success' => true,
                    'msg'     => __('lang_v1.deleted_success'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
