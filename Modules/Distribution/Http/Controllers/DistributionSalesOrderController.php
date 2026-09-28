<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Entities\Core\Contact;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\SalesAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Modules\Distribution\Entities\DistributionSalesOrder;
use Modules\Distribution\Entities\DistributionSalesOrderLine;
use Modules\Distribution\Services\SalesOrders\DistributionSalesOrderPaymentService;
use Yajra\DataTables\Facades\DataTables;

class DistributionSalesOrderController extends Controller
{
    public function index(Request $request)
    {
        // dd('masok');
        $business_id = session()->get('user.business_id');

        // Data for List Tab
        $query = DistributionSalesOrder::where('business_id', $business_id)
            ->with(['customer', 'invoices', 'createdByUser', 'updatedByUser'])
            ->withCount('lines');

        if ($request->filled('shipping_status')) {
            $query->where('shipping_status', $request->shipping_status);
        }
        if ($request->filled('status')) {
            if ($request->status === 'created_invoice') {
                $query->where('status', 'like', 'created_invoice_no %');
            } else {
                $query->where('status', $request->status);
            }
        }
        if ($request->filled('customer_lookup')) {
            $query->where('customer_id', $request->customer_lookup);
        } elseif ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }
        if ($request->filled('sales_order_no')) {
            $query->where('sales_order_no', 'like', '%' . $request->sales_order_no . '%');
        }
        if ($request->filled('invoice_no')) {
            $invoiceNo = $request->invoice_no;
            $query->whereHas('invoices', function ($invoiceQuery) use ($invoiceNo) {
                $invoiceQuery->where('invoice_no', 'like', '%' . $invoiceNo . '%');
            });
        }
        if ($request->filled('location')) {
            $query->where('customer_address', 'like', '%' . $request->location . '%');
        }
        if ($request->filled('customer_contact')) {
            $query->where('customer_contact', 'like', '%' . $request->customer_contact . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }
        if ($request->filled('payment_status')) {
            $dueCondition = function ($invoiceQuery) {
                $invoiceQuery->whereRaw('(grand_total - payment_total) > 0.009');
            };
            if ($request->payment_status === 'due') {
                $query->whereHas('invoices', $dueCondition);
            } elseif ($request->payment_status === 'paid') {
                $query->whereHas('invoices')
                    ->whereDoesntHave('invoices', $dueCondition);
            }
        }
        if ($request->filled('payment_method')) {
            $paymentMethodMap = [
                'cash' => 'payment_cash',
                'card' => 'payment_card',
                'cheque' => 'payment_cheque',
                'credit' => 'payment_credit',
            ];
            $method = $request->payment_method;
            if (isset($paymentMethodMap[$method])) {
                $column = $paymentMethodMap[$method];
                $query->whereHas('invoices', function ($invoiceQuery) use ($column) {
                    $invoiceQuery->where($column, '>', 0);
                });
            }
        }
        if ($request->filled('added_by')) {
            $query->where('created_by', $request->added_by);
        }

        if ($request->ajax()) {
            $query = DistributionSalesOrder::where('distribution_sales_orders.business_id', $business_id)
                ->leftJoin('contacts', 'distribution_sales_orders.customer_id', '=', 'contacts.id')
                ->leftJoin('users as added_by_user', 'distribution_sales_orders.created_by', '=', 'added_by_user.id')
                ->select([
                    'distribution_sales_orders.*',
                    'contacts.name as customer_name_orig',
                    'contacts.mobile as customer_mobile',
                    'added_by_user.username as added_by_username',
                    'added_by_user.first_name as added_by_first_name'
                ])
                ->with(['invoices', 'createdByUser'])
                ->withCount('lines');

            if ($request->filled('shipping_status')) {
                $query->where('distribution_sales_orders.shipping_status', $request->shipping_status);
            }
            if ($request->filled('status')) {
                if ($request->status === 'created_invoice') {
                    $query->where('distribution_sales_orders.status', 'like', 'created_invoice_no %');
                } else {
                    $query->where('distribution_sales_orders.status', $request->status);
                }
            }
            if ($request->filled('customer_lookup')) {
                $query->where('distribution_sales_orders.customer_id', $request->customer_lookup);
            } elseif ($request->filled('customer_id')) {
                $query->where('distribution_sales_orders.customer_id', $request->customer_id);
            }
            if ($request->filled('sales_order_no')) {
                $query->where('distribution_sales_orders.sales_order_no', 'like', '%' . $request->sales_order_no . '%');
            }
            if ($request->filled('invoice_no')) {
                $invoiceNo = $request->invoice_no;
                $query->whereHas('invoices', function ($invoiceQuery) use ($invoiceNo) {
                    $invoiceQuery->where('invoice_no', 'like', '%' . $invoiceNo . '%');
                });
            }
            if ($request->filled('location')) {
                $query->where('distribution_sales_orders.customer_address', 'like', '%' . $request->location . '%');
            }
            if ($request->filled('customer_contact')) {
                $query->where('distribution_sales_orders.customer_contact', 'like', '%' . $request->customer_contact . '%');
            }
            if ($request->filled('date_from')) {
                $query->whereDate('distribution_sales_orders.date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('distribution_sales_orders.date', '<=', $request->date_to);
            }
            if ($request->filled('payment_status')) {
                $dueCondition = function ($invoiceQuery) {
                    $invoiceQuery->whereRaw('(grand_total - payment_total) > 0.009');
                };
                if ($request->payment_status === 'due') {
                    $query->whereHas('invoices', $dueCondition);
                } elseif ($request->payment_status === 'paid') {
                    $query->whereHas('invoices')
                        ->whereDoesntHave('invoices', $dueCondition);
                }
            }
            if ($request->filled('payment_method')) {
                $paymentMethodMap = [
                    'cash' => 'payment_cash',
                    'card' => 'payment_card',
                    'cheque' => 'payment_cheque',
                    'credit' => 'payment_credit',
                ];
                $method = $request->payment_method;
                if (isset($paymentMethodMap[$method])) {
                    $column = $paymentMethodMap[$method];
                    $query->whereHas('invoices', function ($invoiceQuery) use ($column) {
                        $invoiceQuery->where($column, '>', 0);
                    });
                }
            }
            if ($request->filled('added_by')) {
                $query->where('distribution_sales_orders.created_by', $request->added_by);
            }

            $activityPayloads = $this->getDistributionActivityPayloads(
                $query->pluck('id')->all(),
                DistributionSalesOrder::class
            );

            $invoiceNos = [];
            $allOrders = $query->get();
            foreach ($allOrders as $order) {
                foreach ($order->invoices as $invoice) {
                    if (!empty($invoice->invoice_no)) {
                        $invoiceNos[] = $invoice->invoice_no;
                    }
                }
            }
            $invoiceNos = array_values(array_unique($invoiceNos));
            $transactionByInvoiceNo = [];
            if (!empty($invoiceNos)) {
                $transactionByInvoiceNo = \Modules\Distribution\Entities\Core\Transaction::where('business_id', $business_id)
                    ->whereIn('invoice_no', $invoiceNos)
                    ->pluck('id', 'invoice_no')
                    ->toArray();
            }

            return DataTables::of($query)
                ->addColumn('action', function ($row) use ($transactionByInvoiceNo, $activityPayloads) {
                    $firstInvoice = $row->invoices->first();
                    $row->first_invoice = $firstInvoice;
                    $row->linked_transaction_id = null;
                    if ($firstInvoice && !empty($firstInvoice->invoice_no)) {
                        $row->linked_transaction_id = $transactionByInvoiceNo[$firstInvoice->invoice_no] ?? null;
                    }
                    $paid_amount = 0;
                    foreach ($row->invoices as $inv) {
                        $paid_amount += (float) ($inv->payment_total ?? 0);
                    }
                    $row->balance_due = max(0, (float)$row->grand_total - $paid_amount);
                    $row->activity_payloads = $activityPayloads[$row->id] ?? $this->fallbackSalesOrderActivityPayloads($row);

                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">
                            Action <span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if ($row->invoices->count() == 0) {
                        $html .= '<li><a href="' . route('distribution.invoices.create', ['sales_order_id' => $row->id]) . '"><i class="fa fa-file-text"></i> Create Sale Invoice</a></li>';
                    }

                    if ($row->balance_due > 0) {
                        if (!empty($row->linked_transaction_id)) {
                            $html .= '<li><a href="' . action('TransactionPaymentController@addPayment', [$row->linked_transaction_id]) . '" class="add_payment_modal"><i class="fa fa-money"></i> Pay Due Amount</a></li>';
                        } else {
                            $html .= '<li><a href="' . url('customer-payments?customer_id=' . $row->customer_id . '&amount=' . $row->balance_due) . '"><i class="fa fa-money"></i> Pay Due Amount</a></li>';
                        }
                    }

                    $html .= '<li><a href="' . route('distribution.sales_orders.show', $row->id) . '"><i class="fa fa-eye"></i> View</a></li>';

                    $html .= '<li><a href="' . route('distribution.sales_orders.payments', $row->id) . '" class="view_payment_modal" data-container=".view_modal"><i class="fa fa-list"></i> View Payment</a></li>';

                    $html .= '<li><a href="' . route('distribution.sales_orders.edit', $row->id) . '"><i class="fa fa-edit"></i> Edit</a></li>';
                    $html .= '<li><a href="' . route('distribution.sales_orders.print', $row->id) . '" target="_blank"><i class="fa fa-print"></i> Print</a></li>';
                    $html .= '<li><a href="' . route('distribution.sales_orders.duplicate', $row->id) . '"><i class="fa fa-copy"></i> Duplicate Sales Order</a></li>';
                    $html .= '<li><a href="' . route('distribution.sales_orders.show_url', $row->id) . '" class="view_sales_order_url"><i class="fa fa-link"></i> Sales Order URL</a></li>';

                    $html .= '<li><a href="#" class="show-so-notes-btn" 
                        data-invoice-note="' . e($row->invoice_note ?? '') . '"
                        data-shipping-note="' . e($row->shipping_note ?? '') . '"
                        data-sales-order-no="' . e($row->sales_order_no ?? '-') . '"><i class="fa fa-book"></i> Notes</a></li>';

                    $invoicedText = '-';
                    if ($row->invoices && $row->invoices->count() && $row->first_invoice) {
                        $invoicedText = optional($row->first_invoice->created_at)->format('Y-m-d H:i:s') . ', Sale Invoice created by ' . (optional($row->first_invoice->addedUser)->username ?? optional($row->first_invoice->addedUser)->first_name ?? 'System');
                    }
                    $html .= '<li><a href="#" class="view-so-changed-activities" data-id="' . $row->id . '"><i class="fa fa-history"></i> Changed Activities</a></li>';

                    if ($row->invoices->count() == 0) {
                        $html .= '<li class="divider"></li>
                        <li>
                            <form method="POST" action="' . route('distribution.sales_orders.destroy', $row->id) . '" onsubmit="return confirm(\'Delete this sales order?\');" style="display:inline;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit" style="background:none; border:none; padding:3px 20px; color:#333; width:100%; text-align:left;"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </li>';
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->addColumn('added_by', function ($row) {
                    return $row->added_by_username ?? $row->added_by_first_name ?? '-';
                })
                ->editColumn('date', function ($row) {
                    return \Carbon\Carbon::parse($row->date)->format('Y-m-d H:i');
                })
                ->editColumn('sales_order_no', function ($row) {
                    return $row->sales_order_no;
                })
                ->addColumn('sales_invoice_no', function ($row) {
                    if ($row->invoices && $row->invoices->count()) {
                        return $row->invoices->pluck('invoice_no')->implode(', ');
                    }
                    return '-';
                })
                ->editColumn('delivery_date', function ($row) {
                    return $row->delivery_date ?: '-';
                })
                ->addColumn('customer_name', function ($row) {
                    $name = $row->customer_name ?: $row->customer_name_orig;
                    $contact = !empty($row->customer_contact) ? ' / ' . $row->customer_contact : '';
                    return $name . $contact;
                })
                ->editColumn('customer_address', function ($row) {
                    return $row->customer_address ?: '-';
                })
                ->addColumn('total_items', function ($row) {
                    return (int) ($row->lines_count ?? 0);
                })
                ->editColumn('status', function ($row) {
                    $currentStatus = $row->status;
                    $displayStatus = strpos($currentStatus, 'created_invoice_no') === 0 
                        ? str_replace('created_invoice_no', 'Created Sales Invoice No', $currentStatus) 
                        : ucfirst($currentStatus);

                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                            ' . $displayStatus . ' <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Current Status: ' . $displayStatus . '</li>
                            <li class="divider"></li>';

                    $options = [
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ];

                    foreach ($options as $status_key => $status_label) {
                        if ($currentStatus != $status_key) {
                            $html .= '<li>
                                <form method="POST" action="' . route('distribution.sales_orders.status', $row->id) . '" class="so-status-form">
                                    ' . csrf_field() . '
                                    <input type="hidden" name="status" value="' . $status_key . '">
                                    <button type="submit" class="change-status-btn" style="background:none; border:none; padding:3px 20px; color:#333; width:100%; text-align:left;">
                                        ' . $status_label . '
                                    </button>
                                </form>
                            </li>';
                        }
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('grand_total', function ($row) {
                    return number_format($row->grand_total, 2);
                })
                ->addColumn('payment_status', function ($row) {
                    $paid_amount = 0;
                    foreach ($row->invoices as $inv) {
                        $paid_amount += (float) ($inv->payment_total ?? 0);
                    }
                    $balance_due = max(0, (float)$row->grand_total - $paid_amount);

                    if ($balance_due >= $row->grand_total && $row->grand_total > 0) {
                        return '<span class="label label-danger">Due</span>';
                    } elseif ($balance_due > 0 && $balance_due < $row->grand_total) {
                        return '<span class="label label-warning">Partial</span>';
                    } elseif ($balance_due == 0 && $row->grand_total > 0) {
                        return '<span class="label label-success">Paid</span>';
                    } else {
                        return '<span class="label label-default">N/A</span>';
                    }
                })
                ->addColumn('paid_amount', function ($row) {
                    $paid_amount = 0;
                    foreach ($row->invoices as $inv) {
                        $paid_amount += (float) ($inv->payment_total ?? 0);
                    }
                    return number_format($paid_amount, 2);
                })
                ->addColumn('payment_method', function ($row) {
                    $methods = [];
                    $cash_total = 0;
                    $card_total = 0;
                    $cheque_total = 0;
                    $credit_total = 0;
                    $paid_amount = 0;

                    if ($row->invoices->count() > 0) {
                        // Ambil payment dari invoice terkait
                        foreach ($row->invoices as $inv) {
                            $paid_amount += (float) ($inv->payment_total ?? 0);
                            $c_cash   = (float) ($inv->payment_cash ?? 0);
                            $c_card   = (float) ($inv->payment_card ?? 0);
                            $c_cheque = (float) ($inv->payment_cheque ?? 0);
                            $c_credit = (float) ($inv->payment_credit ?? 0);

                            $cash_total   += $c_cash;
                            $card_total   += $c_card;
                            $cheque_total += $c_cheque;
                            $credit_total += $c_credit;

                            if ($c_cash > 0)   $methods['Cash']   = true;
                            if ($c_card > 0)   $methods['Card']   = true;
                            if ($c_cheque > 0) $methods['Cheque'] = true;
                            if ($c_credit > 0) $methods['Credit'] = true;
                        }
                    } else {
                        // Ambil payment dari SO langsung (belum ada invoice)
                        $paid_amount  = (float) ($row->payment_total ?? 0);
                        $cash_total   = (float) ($row->payment_cash ?? 0);
                        $card_total   = (float) ($row->payment_card ?? 0);
                        $cheque_total = (float) ($row->payment_cheque ?? 0);
                        $credit_total = (float) ($row->payment_credit ?? 0);

                        if ($cash_total > 0)   $methods['Cash']   = true;
                        if ($card_total > 0)   $methods['Card']   = true;
                        if ($cheque_total > 0) $methods['Cheque'] = true;
                        if ($credit_total > 0) $methods['Credit'] = true;
                    }

                    $payment_methods = empty($methods) ? '-' : implode(', ', array_keys($methods));

                    $html = $payment_methods;
                    // Tampilkan button Payment Details hanya jika ada pembayaran (bukan Due)
                    if ($paid_amount > 0) {
                        $html .= '<br><button type="button" class="btn btn-info btn-xs show-so-payment-btn"
                            data-cash="' . number_format($cash_total, 2) . '"
                            data-card="' . number_format($card_total, 2) . '"
                            data-cheque="' . number_format($cheque_total, 2) . '"
                            data-credit="' . number_format($credit_total, 2) . '"
                            data-total="' . number_format($paid_amount, 2) . '">
                            Payment Details
                        </button>';
                    }
                    return $html;
                })
                ->addColumn('shipping_status', function ($row) {
                    $currentStatus = $row->shipping_status ?? 'ordered';
                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown">
                            ' . ucfirst($currentStatus) . ' <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li class="dropdown-header">Current Status: ' . ucfirst($currentStatus) . '</li>
                            <li class="divider"></li>';

                    foreach (['ordered' => 'Ordered', 'packed' => 'Packed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'] as $status_key => $status_label) {
                        if ($currentStatus != $status_key) {
                            $html .= '<li>
                                <form method="POST" action="' . route('distribution.sales_orders.shipping_status', $row->id) . '" class="so-shipping-status-form">
                                    ' . csrf_field() . '
                                    <input type="hidden" name="shipping_status" value="' . $status_key . '">
                                    <button type="submit" class="change-shipping-status-btn" style="background:none; border:none; padding:3px 20px; color:#333; width:100%; text-align:left;">
                                        ' . $status_label . '
                                    </button>
                                </form>
                            </li>';
                        }
                    }
                    $html .= '</ul></div>';
                    return $html;
                })
                ->rawColumns(['action', 'payment_status', 'payment_method', 'shipping_status', 'status'])
                ->make(true);
        }

        $sales_orders = $query->withCount('lines')
            ->with(['invoices', 'createdByUser'])
            ->orderByDesc('id')
            ->get();
        
        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->select('id', 'name', 'mobile', 'landline', 'address_line_1', 'landmark', 'address')
            ->orderBy('name')
            ->get();
            
        $users = \Modules\Distribution\Entities\Core\User::forDropdown($business_id, false);
        $salesOrderNos = DistributionSalesOrder::where('business_id', $business_id)->pluck('sales_order_no', 'sales_order_no');
        $locations = \Modules\Distribution\Entities\Core\BusinessLocation::forDropdown($business_id);

        // Data for Create Tab
        $business = Business::find($business_id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)->first() ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();
            
        // Sales Rep dropdown must show Distribution Sales Agents only.
        // The SalesAgent model uses the distribution_sales_agents table.
        $salesReps = SalesAgent::forBusiness($business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $default_sales_rep_id = SalesAgent::where('business_id', $business_id)
            ->where('user_id', auth()->id())
            ->value('id');

        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->where('not_for_selling', 0)->pluck('name', 'id');

        $sales_order_no = $this->generateSalesOrderNumber(false);

        $activityPayloads = $this->getDistributionActivityPayloads(
            $sales_orders->pluck('id')->all(),
            DistributionSalesOrder::class
        );
        $invoiceNos = [];
        foreach ($sales_orders as $order) {
            foreach ($order->invoices as $invoice) {
                if (!empty($invoice->invoice_no)) {
                    $invoiceNos[] = $invoice->invoice_no;
                }
            }
        }
        $invoiceNos = array_values(array_unique($invoiceNos));
        $transactionByInvoiceNo = [];
        if (!empty($invoiceNos)) {
            $transactionByInvoiceNo = \Modules\Distribution\Entities\Core\Transaction::where('business_id', $business_id)
                ->whereIn('invoice_no', $invoiceNos)
                ->pluck('id', 'invoice_no')
                ->toArray();
        }
        $sales_orders->transform(function ($order) use ($transactionByInvoiceNo, $activityPayloads) {
            $firstInvoice = $order->invoices->first();
            $order->first_invoice = $firstInvoice;
            $order->linked_transaction_id = null;
            $order->paid_amount = 0;
            $order->balance_due = 0;
            $order->payment_methods = '-';

            $paid_amount = 0;
            $methods = [];
            $cash_total = 0;
            $card_total = 0;
            $cheque_total = 0;
            $credit_total = 0;
            
            foreach ($order->invoices as $inv) {
                $paid_amount += (float) ($inv->payment_total ?? 0);
                
                $c_cash = (float) ($inv->payment_cash ?? 0);
                $c_card = (float) ($inv->payment_card ?? 0);
                $c_cheque = (float) ($inv->payment_cheque ?? 0);
                $c_credit = (float) ($inv->payment_credit ?? 0);
                
                $cash_total += $c_cash;
                $card_total += $c_card;
                $cheque_total += $c_cheque;
                $credit_total += $c_credit;
                
                if ($c_cash > 0) $methods['Cash'] = true;
                if ($c_card > 0) $methods['Card'] = true;
                if ($c_cheque > 0) $methods['Cheque'] = true;
                if ($c_credit > 0) $methods['Credit'] = true;
            }
            $order->paid_amount = $paid_amount;
            $order->cash_total = $cash_total;
            $order->card_total = $card_total;
            $order->cheque_total = $cheque_total;
            $order->credit_total = $credit_total;
            
            $order->balance_due = max(0, (float)$order->grand_total - $paid_amount);
            $order->payment_methods = empty($methods) ? '-' : implode(', ', array_keys($methods));
            $order->display_status = strpos((string) $order->status, 'created_invoice_no') === 0
                ? str_replace('created_invoice_no', 'Created Sales Invoice No', (string) $order->status)
                : ucfirst((string) $order->status);

            if ($firstInvoice && !empty($firstInvoice->invoice_no)) {
                $order->linked_transaction_id = $transactionByInvoiceNo[$firstInvoice->invoice_no] ?? null;
            }
            $order->activity_payloads = $activityPayloads[$order->id] ?? $this->fallbackSalesOrderActivityPayloads($order);
            return $order;
        });

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'mobile', 'landline', 'address_line_1', 'landmark', 'address']);

        // Extra locations for List Tab filter
        $list_locations = DistributionSalesOrder::where('business_id', $business_id)
            ->whereNotNull('customer_address')
            ->where('customer_address', '!=', '')
            ->orderBy('customer_address')
            ->pluck('customer_address', 'customer_address');

        return view('distribution::sales_orders.index', compact(
            'sales_orders', 
            'customers', 
            'users', 
            'salesOrderNos', 
            'locations',
            'list_locations',
            'business',
            'location',
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'products',
            'sales_order_no',
            'default_sales_rep_id'
        ));
    }

    public function create()
    {
        $business_id = session()->get('user.business_id');
        $business = Business::find($business_id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)->first() ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->select('id', 'name', 'mobile', 'landline', 'address_line_1', 'landmark', 'address')
            ->orderBy('name')
            ->get();

        $salesReps = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id');
        $default_sales_rep_id = SalesAgent::where('business_id', $business_id)
            ->where('user_id', auth()->id())
            ->value('id');
        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->where('not_for_selling', 0)->pluck('name', 'id');

        $sales_order_no = $this->generateSalesOrderNumber(false);

        return view('distribution::sales_orders.create', compact(
            'business',
            'location',
            'customers',
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'products',
            'sales_order_no',
            'default_sales_rep_id'
        ));
    }

    public function store(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'customer_id' => 'required|integer',
            'date' => 'required',
            'product_id' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $customer = Contact::findOrFail($request->customer_id);
            $dateTime = str_replace('T', ' ', $request->date);
            if (strlen($dateTime) === 16) {
                $dateTime .= ':00';
            }

            $order = DistributionSalesOrder::create([
                'business_id' => $business_id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_contact' => $customer->mobile ?? $customer->landline,
                'customer_address' => $customer->landmark ?? $customer->address_line_1,
                'date' => $dateTime,
                'delivery_date' => $request->delivery_date,
                'sales_rep_id' => $request->sales_rep_id,
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'category_id' => $request->category_id,
                'sales_order_no' => $this->generateSalesOrderNumber(true),
                'loading_sheet_no' => $request->loading_sheet_no,
                'invoice_note' => $request->invoice_note,
                'shipping_note' => $request->shipping_note,
                'shipping_details' => $request->shipping_details,
                'shipping_status' => $request->shipping_status ?: 'ordered',
                'status' => $request->status ?: 'active',
                'total' => 0,
                'discount' => 0,
                'grand_total' => 0,
                'payment_cash'   => (float) ($request->payment_cash ?? 0),
                'payment_card'   => (float) ($request->payment_card ?? 0),
                'payment_credit' => (float) ($request->payment_credit ?? 0),
                'payment_cheque' => (float) ($request->payment_cheque ?? 0),
                'payment_total'  => (float) ($request->payment_cash ?? 0)
                                  + (float) ($request->payment_card ?? 0)
                                  + (float) ($request->payment_credit ?? 0)
                                  + (float) ($request->payment_cheque ?? 0),
                'created_by' => auth()->id(),
            ]);

            $total = 0;
            $discount = 0;
            foreach ($request->product_id as $i => $product_id) {
                if (empty($product_id)) {
                    continue;
                }
                $qty = (float)($request->qty[$i] ?? 0);
                $unit_price = (float)($request->unit_price[$i] ?? 0);
                $amount = $qty * $unit_price;
                $line_discount = (float)($request->discount[$i] ?? 0);
                $final_amount = max(0, $amount - $line_discount);

                DistributionSalesOrderLine::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product_id,
                    'unit_id' => $request->unit_id[$i] ?? null,
                    'qty' => $qty,
                    'unit_price' => $unit_price,
                    'amount' => $amount,
                    'discount' => $line_discount,
                    'discount_type' => $request->discount_type[$i] ?? 'fixed',
                    'final_amount' => $final_amount,
                    'is_free' => (int)($request->is_free[$i] ?? 0),
                    'is_free_bottles' => (int)($request->is_free_bottles[$i] ?? 0),
                    'is_free_auto' => (int)($request->is_free_auto[$i] ?? 0),
                ]);
                $total += $amount;
                $discount += $line_discount;
            }

            $order->update([
                'total' => $total,
                'discount' => $discount,
                'grand_total' => max(0, $total - $discount),
            ]);

            DB::commit();
            return redirect()->route('distribution.sales_orders.index')->with('status', 'Sales order created successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $business_id = session()->get('user.business_id');
        $sales_order = DistributionSalesOrder::where('business_id', $business_id)
            ->with(['customer', 'lines.product', 'invoices'])
            ->findOrFail($id);

        return view('distribution::sales_orders.show', compact('sales_order'));
    }

    public function print($id)
    {
        $business_id = session()->get('user.business_id');
        $sales_order = DistributionSalesOrder::where('business_id', $business_id)
            ->with(['customer', 'lines.product', 'invoices'])
            ->findOrFail($id);
        $business = Business::find($business_id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)->first() ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        return view('distribution::sales_orders.print', compact('sales_order', 'business', 'location'));
    }

    public function edit($id)
    {
        $business_id = session()->get('user.business_id');
        $business = Business::find($business_id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)->first() ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        $sales_order = DistributionSalesOrder::where('business_id', $business_id)->with('lines')->findOrFail($id);

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->select('id', 'name', 'mobile', 'landline', 'address_line_1', 'landmark', 'address')
            ->orderBy('name')
            ->get();

        $salesReps = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id');
        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');
        $products = Product::where('business_id', $business_id)->where('not_for_selling', 0)->pluck('name', 'id');

        return view('distribution::sales_orders.edit', compact(
            'business',
            'location',
            'sales_order',
            'customers',
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'products'
        ));
    }

    public function update(Request $request, $id)
    {
        // dd($request);
        $business_id = session()->get('user.business_id');
        $sales_order = DistributionSalesOrder::where('business_id', $business_id)->findOrFail($id);

        DB::beginTransaction();
        try {
            $dateTime = str_replace('T', ' ', $request->date);
            if (strlen($dateTime) === 16) {
                $dateTime .= ':00';
            }

        
            $sales_order->update([
                'customer_id' => $request->customer_id,
                'date' => $dateTime,
                'delivery_date' => $request->delivery_date,
                'sales_rep_id' => $request->sales_rep_id,
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'category_id' => $request->category_id,
                'loading_sheet_no' => $request->loading_sheet_no,
                'invoice_note' => $request->invoice_note,
                'shipping_note' => $request->shipping_note,
                'shipping_details' => $request->shipping_details,
                'shipping_status' => $request->shipping_status ?: 'ordered',
                'status' => $request->status ?: 'active',
                'updated_by' => auth()->id(),
            ]);

            $sales_order->lines()->delete();
            $total = 0;
            $discount = 0;

            //  dd($request->product_id);
            foreach (($request->product_id ?? []) as $i => $product_id) {
                if (empty($product_id)) {
                    continue;
                }
                $qty = (float)($request->qty[$i] ?? 0);
                $unit_price = (float)($request->unit_price[$i] ?? 0);
                $amount = $qty * $unit_price;
                $line_discount = (float)($request->discount[$i] ?? 0);
                $final_amount = max(0, $amount - $line_discount);

                DistributionSalesOrderLine::create([
                    'sales_order_id' => $sales_order->id,
                    'product_id' => $product_id,
                    'unit_id' => $request->unit_id[$i] ?? null,
                    'qty' => $qty,
                    'unit_price' => $unit_price,
                    'amount' => $amount,
                    'discount' => $line_discount,
                    'discount_type' => $request->discount_type[$i] ?? 'fixed',
                    'final_amount' => $final_amount,
                    'is_free' => (int)($request->is_free[$i] ?? 0),
                    'is_free_bottles' => (int)($request->is_free_bottles[$i] ?? 0),
                    'is_free_auto' => (int)($request->is_free_auto[$i] ?? 0),
                ]);
                $total += $amount;
                $discount += $line_discount;
            }

            $sales_order->update([
                'total' => $total,
                'discount' => $discount,
                'grand_total' => max(0, $total - $discount),
            ]);

            DB::commit();
            return redirect()->route('distribution.sales_orders.index')->with('status', 'Sales order updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function duplicate($id)
    {
        $business_id = session()->get('user.business_id');
        $sales_order = DistributionSalesOrder::where('business_id', $business_id)->with('lines')->findOrFail($id);

        DB::beginTransaction();
        try {
            $newOrder = $sales_order->replicate();
            $newOrder->sales_order_no = $this->generateSalesOrderNumber(true);
            $newOrder->created_at = now();
            $newOrder->updated_at = now();
            $newOrder->save();

            foreach ($sales_order->lines as $line) {
                $newLine = $line->replicate();
                $newLine->sales_order_id = $newOrder->id;
                $newLine->save();
            }
            DB::commit();

            return redirect()->route('distribution.sales_orders.edit', $newOrder->id)
                ->with('status', 'Sales order duplicated. Please review and save.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    public function destroy($id)
    {
        $business_id = session()->get('user.business_id');
        $sales_order = DistributionSalesOrder::where('business_id', $business_id)
            ->with('invoices')
            ->findOrFail($id);

        if ($sales_order->invoices && $sales_order->invoices->count() > 0) {
            return back()->withErrors('Cannot delete sales order: linked invoice exists.');
        }

        DB::beginTransaction();
        try {
            $sales_order->lines()->delete();
            $sales_order->delete();
            DB::commit();

            return back()->with('status', 'Sales order deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withErrors($e->getMessage());
        }
    }

    public function showPayments($id)
    {
        // Backward-compatible method retained for any old route/cache reference.
        // New routes use DistributionSalesOrderPaymentController.
        $paymentService = app(DistributionSalesOrderPaymentService::class);
        $business_id = (int) session()->get('user.business_id');
        $sales_order = $paymentService->findSalesOrder($business_id, (int) $id);
        $rows = $paymentService->paymentRows($sales_order);
        $total = $paymentService->totalPaid($rows);
        $balance_due = $paymentService->balanceDue($sales_order, $total);

        return view('distribution::sales_orders.payments.show', compact('sales_order', 'rows', 'total', 'balance_due'));
    }

    public function updateShippingStatus(Request $request, $id)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'shipping_status' => 'required|in:ordered,packed,shipped,delivered,cancelled',
        ]);

        $sales_order = DistributionSalesOrder::where('business_id', $business_id)->findOrFail($id);
        $sales_order->shipping_status = $request->shipping_status;
        $sales_order->save();

        return back()->with('status', 'Sales order shipping status updated successfully.');
    }


    public function getActivityLog($id)
    {
        $business_id = session()->get('user.business_id');
        $salesOrder = DistributionSalesOrder::where('business_id', $business_id)->findOrFail($id);

        $activities = Activity::where('subject_type', DistributionSalesOrder::class)
            ->where('subject_id', $salesOrder->id)
            ->with(['causer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('distribution::sales_orders.partials.activity_log_popup', compact('activities', 'salesOrder'));
    }


    private function getDistributionActivityPayloads(array $subjectIds, string $subjectType): array
    {
        if (empty($subjectIds)) {
            return [];
        }

        $activities = Activity::where('subject_type', $subjectType)
            ->whereIn('subject_id', $subjectIds)
            ->orderBy('created_at')
            ->get();

        $payloads = [];
        foreach ($activities as $activity) {
            $subjectId = (int) $activity->subject_id;
            if (!isset($payloads[$subjectId])) {
                $payloads[$subjectId] = [
                    'created' => '-',
                    'changed' => '-',
                    'deleted' => '-',
                ];
            }

            $description = strtolower((string) $activity->description);
            $line = $this->formatDistributionActivityLine($activity);

            if (in_array($description, ['created', 'create'], true)) {
                $payloads[$subjectId]['created'] = $line;
            } elseif (in_array($description, ['updated', 'update'], true)) {
                $payloads[$subjectId]['changed'] = $line;
            } elseif (in_array($description, ['deleted', 'delete'], true)) {
                $payloads[$subjectId]['deleted'] = $line;
            }
        }

        return $payloads;
    }

    private function formatDistributionActivityLine(Activity $activity): string
    {
        $properties = $activity->properties;
        if (is_object($properties) && method_exists($properties, 'toArray')) {
            $properties = $properties->toArray();
        }

        $details = [];
        $attributes = is_array($properties) ? ($properties['attributes'] ?? []) : [];
        $old = is_array($properties) ? ($properties['old'] ?? []) : [];
        $labels = $this->distributionActivityLabels();

        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $old) && (string) $old[$key] !== (string) $value) {
                $details[] = ($labels[$key] ?? $key) . ': '
                    . $this->formatDistributionActivityValue($key, $old[$key])
                    . ' -> '
                    . $this->formatDistributionActivityValue($key, $value);
            }
        }

        if (empty($details) && !empty($attributes)) {
            foreach ($attributes as $key => $value) {
                $details[] = ($labels[$key] ?? $key) . ': ' . $this->formatDistributionActivityValue($key, $value);
            }
        }

        $causerName = optional($activity->causer)->username
            ?? optional($activity->causer)->first_name
            ?? 'System';

        $base = ucfirst((string) $activity->description) . ' by ' . $causerName . ' on '
            . optional($activity->created_at)->format('Y-m-d H:i:s');

        return empty($details) ? $base : $base . ' | ' . implode(', ', $details);
    }

    private function distributionActivityLabels(): array
    {
        return [
            'sales_order_no' => 'Sales Order No',
            'date' => 'Date & Time',
            'delivery_date' => 'Delivery Date',
            'customer_name' => 'Customer Name',
            'customer_contact' => 'Customer Contact Number',
            'customer_address' => 'Location',
            'grand_total' => 'Total Amount',
            'shipping_status' => 'Shipping Status',
            'status' => 'Sales Order Status',
            'invoice_note' => 'Sales Order Note',
            'shipping_note' => 'Shipping Note',
            'shipping_details' => 'Shipping Details',
        ];
    }

    private function formatDistributionActivityValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($key === 'grand_total') {
            return number_format((float) $value, 2, '.', '');
        }

        if ($key === 'shipping_status' || $key === 'status') {
            return ucfirst(str_replace('_', ' ', (string) $value));
        }

        return (string) $value;
    }

    private function fallbackSalesOrderActivityPayloads(DistributionSalesOrder $salesOrder): array
    {
        return [
            'created' => 'Created by '
                . (optional($salesOrder->createdByUser)->username ?? optional($salesOrder->createdByUser)->first_name ?? 'System')
                . ' on ' . optional($salesOrder->created_at)->format('Y-m-d H:i:s'),
            'changed' => 'Updated by '
                . (optional($salesOrder->updatedByUser)->username ?? optional($salesOrder->updatedByUser)->first_name ?? 'System')
                . ' on ' . optional($salesOrder->updated_at)->format('Y-m-d H:i:s'),
            'deleted' => '-',
        ];
    }

    private function generateSalesOrderNumber(bool $increment): string
    {
        $business_id = session()->get('user.business_id');
        $setting = DB::table('distribution_prefix_settings')
            ->where('business_id', $business_id)
            ->where('numbering_type', 'sales_order')
            ->first();

        if (! $setting) {
            $nextNumber = (DistributionSalesOrder::where('business_id', $business_id)->max('id') ?? 0) + 1;
            return 'SO' . $nextNumber;
        }

        $current = $setting->current_no ?? $setting->starting_no ?? 1;
        $prefix = $setting->prefix ?? '';
        $sales_order_no = $prefix . $current;
        while (DistributionSalesOrder::where('business_id', $business_id)->where('sales_order_no', $sales_order_no)->exists()) {
            $current++;
            $sales_order_no = $prefix . $current;
        }

        if ($increment) {
            DB::table('distribution_prefix_settings')->where('id', $setting->id)->update(['current_no' => $current + 1]);
        }

        return $sales_order_no;
    }

    public function getRoutesByUser(Request $request)
    {
        $business_id = session()->get('user.business_id');

        // Backward-compatible endpoint name. The Sales Order page now sends
        // distribution_sales_agents.id as sales_rep_id, not users.id.
        $sales_rep_id = $request->input('sales_rep_id');

        if (empty($sales_rep_id) && $request->filled('user_id')) {
            $sales_rep_id = SalesAgent::where('business_id', $business_id)
                ->where('user_id', $request->input('user_id'))
                ->value('id');
        }

        if (empty($sales_rep_id)) {
            return response()->json([]);
        }

        $routes_query = DB::table('distribution_routes')
            ->where('business_id', $business_id);

        $mapped_route_ids = DB::table('distribution_route_user_maps')
            ->where('business_id', $business_id)
            ->where('sales_rep_id', $sales_rep_id)
            ->where('status', 'active')
            ->pluck('route_id');

        if ($mapped_route_ids->isNotEmpty()) {
            $routes_query->whereIn('id', $mapped_route_ids);
        } else {
            // If no active mapping exists, show no routes instead of showing all routes.
            $routes_query->whereRaw('1 = 0');
        }

        $routes = $routes_query->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($routes);
    }

    public function showSalesOrderUrl($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $sales_order = DistributionSalesOrder::where('business_id', $business_id)
                ->findOrFail($id);
            $url = route('distribution.sales_orders.show', $sales_order->id);
            return view('distribution::sales_orders.partials.sales_order_url_modal')
                ->with(compact('sales_order', 'url'));
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'status' => 'required|string',
        ]);

        $sales_order = DistributionSalesOrder::where('business_id', $business_id)->findOrFail($id);
        $sales_order->status = $request->status;
        $sales_order->save();

        return back()->with('status', 'Sales order status updated successfully.');
    }
}
