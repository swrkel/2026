<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Entities\Core\Contact;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\SalesAgent;
use Modules\Distribution\Entities\Core\TaxRate;
use Modules\Distribution\Entities\Core\User;
use Modules\Distribution\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\DistributionContactLedger;
use Modules\Distribution\Entities\VatDistributionInvoice;
use Modules\Distribution\Entities\VatDistributionInvoiceLine;
use Modules\Distribution\Entities\VatDistributionInvoiceCheque;
use Modules\Distribution\Entities\Distribution_routes;
use Modules\Distribution\Entities\DistributionVehicles;
use Modules\Distribution\Entities\Distribution_discount_product;
use Modules\Distribution\Entities\Integration\VatInvoice2Prefix;
use Modules\Distribution\Entities\Integration\VatUserInvoicePrefix;
use Modules\Distribution\Entities\Integration\VatInvoice2;

class VatDistributionInvoiceController extends Controller
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        
        $this->middleware(function ($request, $next) {
            $business_id = $request->session()->get('user.business_id');
            if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_dis_invoice') || 
                !$this->moduleUtil->hasThePermissionInSubscription($business_id, 'vat_module')) {
                abort(403, 'VAT Distribution Invoice module is not enabled.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = VatDistributionInvoice::where('business_id', $business_id);
        
        if (request()->filled('shipping_status')) {
            $query->where('shipping_status', request()->shipping_status);
        }
        if (request()->filled('invoice_no')) {
            $query->where('invoice_no', 'like', '%' . request()->invoice_no . '%');
        }
        if (request()->filled('customer_lookup')) {
            $query->where('customer_id', request()->customer_lookup);
        }
        if (request()->filled('customer_name')) {
            $query->where('customer_name', 'like', '%' . request()->customer_name . '%');
        }
        if (request()->filled('customer_contact')) {
            $query->where('customer_contact', 'like', '%' . request()->customer_contact . '%');
        }
        if (request()->filled('location')) {
            $query->where('customer_address', 'like', '%' . request()->location . '%');
        }
        if (request()->filled('date_from')) {
            $query->whereDate('date', '>=', request()->date_from);
        }
        if (request()->filled('date_to')) {
            $query->whereDate('date', '<=', request()->date_to);
        }
        if (request()->filled('payment_status')) {
            if (request()->payment_status === 'due') {
                $query->whereRaw('(grand_total - payment_total) > 0.009');
            } elseif (request()->payment_status === 'paid') {
                $query->whereRaw('(grand_total - payment_total) <= 0.009');
            }
        }
        if (request()->filled('payment_method')) {
            $paymentMethodMap = [
                'cash' => 'payment_cash',
                'card' => 'payment_card',
                'cheque' => 'payment_cheque',
                'credit' => 'payment_credit',
            ];
            $method = request()->payment_method;
            if (isset($paymentMethodMap[$method])) {
                $query->where($paymentMethodMap[$method], '>', 0);
            }
        }
        if (request()->filled('added_by')) {
            $query->where('added_by', request()->added_by);
        }

        $invoices = $query->with(['customer', 'addedUser', 'updatedUser'])
            ->orderBy('id', 'DESC')
            ->paginate(20);

        $invoiceNos = $invoices->getCollection()->pluck('invoice_no')->filter()->values()->all();
        $transactionByInvoiceNo = [];
        if (!empty($invoiceNos)) {
            $transactionByInvoiceNo = \Modules\Distribution\Entities\Core\Transaction::where('business_id', $business_id)
                ->whereIn('invoice_no', $invoiceNos)
                ->pluck('id', 'invoice_no')
                ->toArray();
        }
        $transactionIds = array_values(array_filter(array_values($transactionByInvoiceNo)));
        $paymentByTransactionId = [];
        if (!empty($transactionIds)) {
            $paymentByTransactionId = \Modules\Distribution\Entities\Core\TransactionPayment::whereIn('transaction_id', $transactionIds)
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->get(['id', 'transaction_id'])
                ->unique('transaction_id')
                ->pluck('id', 'transaction_id')
                ->toArray();
        }

        $invoices->getCollection()->transform(function ($inv) use ($transactionByInvoiceNo, $paymentByTransactionId) {
            $inv->linked_transaction_id = $transactionByInvoiceNo[$inv->invoice_no] ?? null;
            $inv->linked_payment_id = !empty($inv->linked_transaction_id)
                ? ($paymentByTransactionId[$inv->linked_transaction_id] ?? null)
                : null;
            $inv->activity_payloads = [
                'created' => '-',
                'changed' => '-',
                'deleted' => '-'
            ];
            return $inv;
        });

        $users = \Modules\Distribution\Entities\Core\User::where('business_id', $business_id)->orderBy('username')->pluck('username', 'id');
        
        $invoiceNos = VatDistributionInvoice::where('business_id', $business_id)
            ->whereNotNull('invoice_no')
            ->orderBy('invoice_no')
            ->pluck('invoice_no', 'invoice_no');

        $customers = VatDistributionInvoice::where('business_id', $business_id)
            ->whereNotNull('customer_id')
            ->orderBy('customer_name')
            ->get(['customer_id', 'customer_name', 'customer_contact'])
            ->unique('customer_id')
            ->mapWithKeys(function ($invoice) {
                $label = $invoice->customer_name ?: 'Customer #' . $invoice->customer_id;
                if (!empty($invoice->customer_contact)) {
                    $label .= ' / ' . $invoice->customer_contact;
                }
                return [$invoice->customer_id => $label];
            });

        $locations = VatDistributionInvoice::where('business_id', $business_id)
            ->whereNotNull('customer_address')
            ->where('customer_address', '!=', '')
            ->orderBy('customer_address')
            ->pluck('customer_address', 'customer_address');

        return view('distribution::vat_invoices.index', compact('invoices', 'users', 'invoiceNos', 'customers', 'locations'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);

        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->first() 
                    ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        $customers = Contact::where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('type', 'customer')->orWhere('type', 'both');
            })
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name ?? trim($c->first_name . ' ' . $c->last_name),
                    'address' => $c->landmark ?? $c->address_line_1 ?? $c->address ?? '',
                    'phone' => $c->mobile ?? $c->landline ?? '',
                    'vat_no' => $c->vat_number ?? ''
                ];
            });

        $salesReps = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id')->toArray();
        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');

        $currency_precision = $business->currency_precision ?? 2;
        $quantity_precision = $business->quantity_precision ?? 2;

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        // Get VAT Prefixes for the user
        $prefixes = VatUserInvoicePrefix::leftJoin('vat_invoice2_prefixes', 'vat_invoice2_prefixes.id', 'vat_user_invoice_prefixes.prefix_id2')
            ->where('vat_user_invoice_prefixes.business_id', $business_id)
            ->where('vat_user_invoice_prefixes.user_id', auth()->id())
            ->pluck('vat_invoice2_prefixes.prefix', 'vat_invoice2_prefixes.id');

        return view('distribution::vat_invoices.create', compact(
            'business', 'location', 'customers', 'salesReps', 'routes', 'vehicles', 'categories',
            'currency_precision', 'quantity_precision', 'taxes', 'tax_attributes', 'prefixes'
        ));
    }

    public function store(Request $request)
    {
        $business_id = session()->get('user.business_id');

        // Validate single product category
        if ($request->filled('category_id')) {
            $category_id = $request->category_id;
            $product_ids = $request->product_id ?? [];
            $is_free_auto = $request->is_free_auto ?? [];
            $is_free = $request->is_free ?? [];
            $is_free_bottles = $request->is_free_bottles ?? [];
            
            $regular_product_ids = [];
            foreach ($product_ids as $index => $pid) {
                $isFreeItem = ($is_free_auto[$index] ?? 0) == 1 || ($is_free[$index] ?? 0) == 1 || ($is_free_bottles[$index] ?? 0) == 1;
                if (!$isFreeItem) {
                    $regular_product_ids[] = $pid;
                }
            }
            $regular_product_ids = array_unique($regular_product_ids);
            if (!empty($regular_product_ids)) {
                $productsInCategory = DB::table('products')
                    ->whereIn('id', $regular_product_ids)
                    ->where('category_id', $category_id)
                    ->count();

                if ($productsInCategory != count($regular_product_ids)) {
                    return back()->withErrors([
                        'category_id' => 'All regular products (non-free items) must belong to the selected product category.'
                    ])->withInput();
                }
            }
        }
        
        DB::beginTransaction();
        try {
            $customer = Contact::find($request->customer_id);
            $invoice_no = $this->generateInvoiceNumber($request->prefix_id, $business_id);

            // Handle date time combination
            $dateTime = $request->date;
            if (strpos($dateTime, ' ') !== false && strpos($dateTime, ':') !== false) {
                // already combined
            } elseif ($request->filled('time')) {
                $dateTime = $request->date . ' ' . $request->time;
            } else {
                $dateTime = $request->date . ' ' . date('H:i:s');
            }

            $paymentCash   = (float) $request->input('payment_cash', 0);
            $paymentCard   = (float) $request->input('payment_card', 0);
            $paymentCredit = (float) $request->input('payment_credit', 0);
            $paymentCheque = (float) $request->input('payment_cheque', 0);
            $paymentTotal  = $paymentCash + $paymentCard + $paymentCredit + $paymentCheque;

            $invoice = VatDistributionInvoice::create([
                'business_id' => $business_id,
                'customer_id' => $request->customer_id,
                'customer_name' => $customer->name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'customer_address' => $customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '',
                'customer_contact' => $customer->mobile ?? $customer->landline ?? '',
                'customer_vat_no' => $request->customer_vat_no,
                'date' => $dateTime,
                'delivery_date' => $request->delivery_date ?: null,
                'sales_rep_id' => $request->sales_rep_id,
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'category_id' => $request->category_id,
                'invoice_no' => $invoice_no,
                'place_of_supply' => $request->place_of_supply,
                'additional_info' => $request->additional_info,
                'invoice_note' => $request->invoice_note,
                'shipping_note' => $request->shipping_note,
                'shipping_details' => $request->shipping_details,
                'shipping_status' => $request->shipping_status ?? 'ordered',
                'status' => $request->status ?? 'active',
                'sales_order_id' => $request->sales_order_id ?: null,
                'total' => 0,
                'discount' => 0,
                'grand_total' => 0,
                'payment_cash' => $paymentCash,
                'payment_card' => $paymentCard,
                'payment_credit' => $paymentCredit,
                'payment_cheque' => $paymentCheque,
                'payment_total' => $paymentTotal,
                'added_by' => auth()->id() ?? 1,
                'updated_by' => auth()->id() ?? 1,
            ]);

            $total = 0;
            $discountTotal = 0;

            foreach ($request->product_id as $i => $pid) {
                if (empty($pid)) continue;

                $isFree = (int) ($request->is_free[$i] ?? 0);
                $isFreeBottles = (int) ($request->is_free_bottles[$i] ?? 0);
                $isFreeAuto = (int) ($request->is_free_auto[$i] ?? 0);
                $isFreeItem = $isFree || $isFreeBottles || $isFreeAuto;

                $qty = (float) $request->qty[$i];
                $unit = (float) $request->unit_price[$i];
                $amount = $isFreeItem ? 0 : ($qty * $unit);

                $userDiscount = (float) ($request->discount[$i] ?? 0);
                $discountType = $request->discount_type[$i] ?? 'fixed';

                $discountAmount = 0;
                if (!$isFreeItem) {
                    $discountInfo = $this->getProductMaxDiscount($pid);
                    $maxAllowed = $discountInfo['max_discount'];

                    if ($discountType === 'percentage') {
                        $discountAmount = ($amount * $userDiscount) / 100;
                        if ($maxAllowed !== null && $userDiscount > $maxAllowed) {
                            DB::rollBack();
                            return back()->withErrors([
                                'discount' => "Discount percentage for product ID {$pid} cannot exceed {$maxAllowed}%",
                            ])->withInput();
                        }
                    } else {
                        $discountAmount = $userDiscount;
                        if ($maxAllowed !== null && $discountAmount > $maxAllowed) {
                            DB::rollBack();
                            return back()->withErrors([
                                'discount' => "Discount amount for product ID {$pid} cannot exceed {$maxAllowed}",
                            ])->withInput();
                        }
                    }
                }

                $finalAmount = $request->final_amount[$i] ?? ($amount - $discountAmount);

                VatDistributionInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $pid,
                    'unit_id' => $request->unit_id[$i] ?? null,
                    'qty' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amount,
                    'discount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'is_free' => $isFree,
                    'is_free_bottles' => $isFreeBottles,
                    'is_free_auto' => $isFreeAuto,
                ]);

                if (!$isFreeItem) {
                    $total += $amount;
                    $discountTotal += $discountAmount;
                }
            }

            $grandTotal = $request->grand_total ?? ($total - $discountTotal);

            $invoice->update([
                'total' => $total,
                'discount' => $discountTotal,
                'grand_total' => $grandTotal
            ]);

            // Save cheques
            $chequeBanks   = $request->input('cheque_bank', []);
            $chequeBranchs = $request->input('cheque_branch', []);
            $chequeNos     = $request->input('cheque_no', []);
            $chequeDates   = $request->input('cheque_date', []);
            $chequeAmounts = $request->input('cheque_amount', []);

            if (!empty($chequeAmounts)) {
                foreach ($chequeAmounts as $i => $amountVal) {
                    $amountVal = (float) $amountVal;
                    if ($amountVal <= 0) continue;

                    VatDistributionInvoiceCheque::create([
                        'invoice_id'  => $invoice->id,
                        'bank'        => $chequeBanks[$i]   ?? null,
                        'branch'      => $chequeBranchs[$i] ?? null,
                        'cheque_no'   => $chequeNos[$i]     ?? null,
                        'cheque_date' => $chequeDates[$i]   ?? null,
                        'amount'      => $amountVal,
                    ]);
                }
            }

            // Create accounting entries
            $this->createAccountingEntries($invoice);

            DB::commit();

            $print_url = action('\Modules\Distribution\Http\Controllers\VatDistributionInvoiceController@show', $invoice->id) . '?print=vat_2026';
            return redirect()->route('distribution.vat-invoices.index')->with('status', [
                'success' => true,
                'msg' => 'VAT Distribution Invoice created successfully!',
                'print_url' => $print_url
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return back()->withErrors('Error: ' . $e->getMessage())->withInput();
        }
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $invoice = VatDistributionInvoice::where('business_id', $business_id)
            ->with(['customer', 'lines.product', 'salesRep', 'route', 'vehicle', 'category'])
            ->findOrFail($id);
        
        $business = Business::find($business_id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->first() 
                    ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        if (request()->has('print')) {
            $format = request()->get('print');
            if ($format == 'vat_2026') {
                return view('distribution::vat_invoices.print_2026', compact('invoice', 'business'));
            }
            if ($format == 'full_vat') {
                return view('distribution::vat_invoices.print_full', compact('invoice', 'business'));
            }
        }
        
        return view('distribution::vat_invoices.show', compact('invoice', 'business', 'location'));
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $invoice = VatDistributionInvoice::where('business_id', $business_id)
            ->with(['lines.product', 'lines.product.unit', 'cheques'])
            ->findOrFail($id);
        
        $business = Business::find($business_id);
        
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->where('is_active', 1)->first() 
                    ?? \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();

        $customers = Contact::where('business_id', $business_id)
            ->where(function ($q) {
                $q->where('type', 'customer')->orWhere('type', 'both');
            })
            ->whereNull('deleted_at')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name ?? trim($c->first_name . ' ' . $c->last_name),
                    'address' => $c->landmark ?? $c->address_line_1 ?? $c->address ?? '',
                    'phone' => $c->mobile ?? $c->landline ?? '',
                    'vat_no' => $c->vat_number ?? ''
                ];
            });

        $salesReps = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id')->toArray();
        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');

        $currency_precision = $business->currency_precision ?? 2;
        $quantity_precision = $business->quantity_precision ?? 2;

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        return view('distribution::vat_invoices.edit', compact(
            'invoice', 'business', 'location', 'customers', 'salesReps', 'routes', 'vehicles', 'categories',
            'currency_precision', 'quantity_precision', 'taxes', 'tax_attributes'
        ));
    }

    public function update(Request $request, $id)
    {
        $business_id = session()->get('user.business_id');
        $invoice = VatDistributionInvoice::where('business_id', $business_id)->findOrFail($id);

        if ($request->filled('category_id')) {
            $category_id = $request->category_id;
            $product_ids = $request->product_id ?? [];
            $is_free_auto = $request->is_free_auto ?? [];
            $is_free = $request->is_free ?? [];
            $is_free_bottles = $request->is_free_bottles ?? [];
            
            $regular_product_ids = [];
            foreach ($product_ids as $index => $pid) {
                $isFreeItem = ($is_free_auto[$index] ?? 0) == 1 || ($is_free[$index] ?? 0) == 1 || ($is_free_bottles[$index] ?? 0) == 1;
                if (!$isFreeItem) {
                    $regular_product_ids[] = $pid;
                }
            }
            $regular_product_ids = array_unique($regular_product_ids);
            if (!empty($regular_product_ids)) {
                $productsInCategory = DB::table('products')
                    ->whereIn('id', $regular_product_ids)
                    ->where('category_id', $category_id)
                    ->count();

                if ($productsInCategory != count($regular_product_ids)) {
                    return back()->withErrors([
                        'category_id' => 'All regular products (non-free items) must belong to the selected product category.'
                    ])->withInput();
                }
            }
        }

        DB::beginTransaction();
        try {
            $dateTime = $request->date;
            if (strpos($dateTime, ' ') !== false && strpos($dateTime, ':') !== false) {
                // already combined
            } elseif ($request->filled('time')) {
                $dateTime = $request->date . ' ' . $request->time;
            } else {
                $dateTime = $request->date . ' ' . date('H:i:s');
            }

            $paymentCash   = (float) $request->input('payment_cash', 0);
            $paymentCard   = (float) $request->input('payment_card', 0);
            $paymentCredit = (float) $request->input('payment_credit', 0);
            $paymentCheque = (float) $request->input('payment_cheque', 0);
            $paymentTotal  = $paymentCash + $paymentCard + $paymentCredit + $paymentCheque;

            $invoice->update([
                'customer_id' => $request->customer_id,
                'customer_vat_no' => $request->customer_vat_no,
                'date' => $dateTime,
                'delivery_date' => $request->delivery_date ?: null,
                'sales_rep_id' => $request->sales_rep_id,
                'route_id' => $request->route_id,
                'vehicle_id' => $request->vehicle_id,
                'category_id' => $request->category_id,
                'place_of_supply' => $request->place_of_supply,
                'additional_info' => $request->additional_info,
                'invoice_note' => $request->invoice_note,
                'shipping_note' => $request->shipping_note,
                'shipping_details' => $request->shipping_details,
                'shipping_status' => $request->shipping_status ?? 'ordered',
                'payment_cash' => $paymentCash,
                'payment_card' => $paymentCard,
                'payment_credit' => $paymentCredit,
                'payment_cheque' => $paymentCheque,
                'payment_total' => $paymentTotal,
                'updated_by' => auth()->id() ?? 1,
            ]);

            $invoice->lines()->delete();
            $invoice->cheques()->delete();

            $total = 0;
            $discountTotal = 0;

            foreach ($request->product_id as $i => $pid) {
                if (empty($pid)) continue;

                $isFree = (int) ($request->is_free[$i] ?? 0);
                $isFreeBottles = (int) ($request->is_free_bottles[$i] ?? 0);
                $isFreeAuto = (int) ($request->is_free_auto[$i] ?? 0);
                $isFreeItem = $isFree || $isFreeBottles || $isFreeAuto;

                $qty = (float) $request->qty[$i];
                $unit = (float) $request->unit_price[$i];
                $amount = $isFreeItem ? 0 : ($qty * $unit);

                $userDiscount = (float) ($request->discount[$i] ?? 0);
                $discountType = $request->discount_type[$i] ?? 'fixed';

                $discountAmount = 0;
                if (!$isFreeItem) {
                    $discountInfo = $this->getProductMaxDiscount($pid);
                    $maxAllowed = $discountInfo['max_discount'];

                    if ($discountType === 'percentage') {
                        $discountAmount = ($amount * $userDiscount) / 100;
                        if ($maxAllowed !== null && $userDiscount > $maxAllowed) {
                            DB::rollBack();
                            return back()->withErrors([
                                'discount' => "Discount percentage for product ID {$pid} cannot exceed {$maxAllowed}%",
                            ])->withInput();
                        }
                    } else {
                        $discountAmount = $userDiscount;
                        if ($maxAllowed !== null && $discountAmount > $maxAllowed) {
                            DB::rollBack();
                            return back()->withErrors([
                                'discount' => "Discount amount for product ID {$pid} cannot exceed {$maxAllowed}",
                            ])->withInput();
                        }
                    }
                }

                $finalAmount = $request->final_amount[$i] ?? ($amount - $discountAmount);

                VatDistributionInvoiceLine::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $pid,
                    'unit_id' => $request->unit_id[$i] ?? null,
                    'qty' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amount,
                    'discount' => $discountAmount,
                    'final_amount' => $finalAmount,
                    'is_free' => $isFree,
                    'is_free_bottles' => $isFreeBottles,
                    'is_free_auto' => $isFreeAuto,
                ]);

                if (!$isFreeItem) {
                    $total += $amount;
                    $discountTotal += $discountAmount;
                }
            }

            $grandTotal = $request->grand_total ?? ($total - $discountTotal);

            $invoice->update([
                'total' => $total,
                'discount' => $discountTotal,
                'grand_total' => $grandTotal
            ]);

            // Save cheques
            $chequeBanks   = $request->input('cheque_bank', []);
            $chequeBranchs = $request->input('cheque_branch', []);
            $chequeNos     = $request->input('cheque_no', []);
            $chequeDates   = $request->input('cheque_date', []);
            $chequeAmounts = $request->input('cheque_amount', []);

            if (!empty($chequeAmounts)) {
                foreach ($chequeAmounts as $i => $amountVal) {
                    $amountVal = (float) $amountVal;
                    if ($amountVal <= 0) continue;

                    VatDistributionInvoiceCheque::create([
                        'invoice_id'  => $invoice->id,
                        'bank'        => $chequeBanks[$i]   ?? null,
                        'branch'      => $chequeBranchs[$i] ?? null,
                        'cheque_no'   => $chequeNos[$i]     ?? null,
                        'cheque_date' => $chequeDates[$i]   ?? null,
                        'amount'      => $amountVal,
                    ]);
                }
            }

            // Create/Update accounting entries
            $this->createAccountingEntries($invoice);

            DB::commit();

            $print_url = action('\Modules\Distribution\Http\Controllers\VatDistributionInvoiceController@show', $invoice->id) . '?print=vat_2026';
            return redirect()->route('distribution.vat-invoices.index')->with('status', [
                'success' => true,
                'msg' => 'VAT Distribution Invoice updated successfully!',
                'print_url' => $print_url
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $business_id = session()->get('user.business_id');
        $invoice = VatDistributionInvoice::where('business_id', $business_id)->findOrFail($id);

        DB::beginTransaction();
        try {
            $invoice->lines()->delete();
            $invoice->cheques()->delete();

            // Find and delete accounting entries
            $transaction = \Modules\Distribution\Entities\Core\Transaction::where('invoice_no', $invoice->invoice_no)
                ->where('business_id', $business_id)
                ->first();
            if ($transaction) {
                \Modules\Distribution\Entities\Core\AccountTransaction::where('transaction_id', $transaction->id)
                    ->whereIn('sub_type', ['dis_invoice', 'dis_invoice_payment', 'dis_invoice_credit'])
                    ->delete();
                DistributionContactLedger::where('transaction_id', $transaction->id)->delete();
                $transaction->delete();
            }

            $invoice->delete();
            DB::commit();

            if (request()->ajax()) {
                return ['success' => true, 'msg' => 'Invoice deleted successfully.'];
            }
            return back()->with('status', 'Invoice deleted successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            if (request()->ajax()) {
                return ['success' => false, 'msg' => $e->getMessage()];
            }
            return back()->withErrors($e->getMessage());
        }
    }

    public function updateShippingStatus(Request $request, $id)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'shipping_status' => 'required|in:ordered,packed,shipped,delivered,cancelled',
        ]);

        $invoice = VatDistributionInvoice::where('business_id', $business_id)->findOrFail($id);
        $invoice->shipping_status = $request->shipping_status;
        $invoice->save();

        if (request()->ajax()) {
            return ['success' => true, 'msg' => 'Shipping status updated successfully.'];
        }
        return back()->with('status', 'Shipping status updated successfully.');
    }

    public function duplicate($id)
    {
        $business_id = session()->get('user.business_id');
        $invoice = VatDistributionInvoice::where('business_id', $business_id)
            ->with(['lines', 'cheques'])
            ->findOrFail($id);

        DB::beginTransaction();
        try {
            $newInvoice = $invoice->replicate();
            
            // Extract prefix from old invoice_no to generate next sequential number
            $original_invoice_no = $invoice->invoice_no;
            $parts = explode('-', $original_invoice_no);
            array_pop($parts);
            $prefix_string = implode('-', $parts);
            
            $prefix = VatInvoice2Prefix::where('business_id', $business_id)
                ->where('prefix', $prefix_string)
                ->first();
            $prefix_id = $prefix ? $prefix->id : null;

            $newInvoice->invoice_no = $this->generateInvoiceNumber($prefix_id, $business_id);
            $newInvoice->save();

            foreach ($invoice->lines as $line) {
                $newLine = $line->replicate();
                $newLine->invoice_id = $newInvoice->id;
                $newLine->save();
            }
            foreach ($invoice->cheques as $cheque) {
                $newCheque = $cheque->replicate();
                $newCheque->invoice_id = $newInvoice->id;
                $newCheque->save();
            }

            // Create accounting entries for duplicate
            $this->createAccountingEntries($newInvoice);

            DB::commit();

            return redirect()->route('distribution.vat-invoices.edit', $newInvoice->id)
                ->with('status', 'Invoice duplicated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    public function getPrefixes($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $prefix = VatInvoice2Prefix::find($id);
        if (!$prefix) {
            return response()->json(['error' => 'Prefix not found'], 404);
        }

        // Search in BOTH VatInvoice2 and VatDistributionInvoice for the last number
        $lastVatInvoice = VatInvoice2::where('business_id', $business_id)->where('prefix', $id)->orderBy('id', 'desc')->first();
        $lastDistInvoice = VatDistributionInvoice::where('business_id', $business_id)
            ->where('invoice_no', 'like', $prefix->prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();
        
        $starting_no_string = (string) $prefix->starting_no;
        $starting_no_numeric = (int) $starting_no_string;
        $pad_length = max(strlen($starting_no_string), 1);
        
        $last_no = 0;
        if ($lastVatInvoice) {
            $curr_arr = explode('-', $lastVatInvoice->customer_bill_no);
            $last_no = max($last_no, (int) end($curr_arr));
        }
        if ($lastDistInvoice && $lastDistInvoice->invoice_no) {
            $curr_arr = explode('-', $lastDistInvoice->invoice_no);
            $last_no = max($last_no, (int) end($curr_arr));
        }

        $next_no = $last_no >= $starting_no_numeric ? ($last_no + 1) : $starting_no_numeric;
        $next_no_padded = str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
        
        return response()->json([
            'prefix' => $prefix->prefix,
            'next_number' => $prefix->prefix . "-" . $next_no_padded
        ]);
    }

    private function getProductMaxDiscount($productId)
    {
        $business_id = request()->session()->get('user.business_id');
        $discount = Distribution_discount_product::select('distribution_discount_products.max_discount', 'distribution_discount_products.discount_type')
            ->join('distribution_discounts', 'distribution_discount_products.discount_id', '=', 'distribution_discounts.id')
            ->where('distribution_discount_products.product_id', $productId)
            ->where('distribution_discounts.business_id', $business_id)
            ->orderBy('distribution_discounts.date_time', 'desc')
            ->first();

        if (!$discount) {
            return ['max_discount' => null, 'discount_type' => null];
        }

        return [
            'max_discount' => $discount->max_discount,
            'discount_type' => $discount->discount_type ?? 'fixed'
        ];
    }

    private function generateInvoiceNumber($prefix_id, $business_id)
    {
        $prefixes = VatInvoice2Prefix::find($prefix_id);
        if (!$prefixes) {
            return 'VAT-' . time();
        }

        // Search in BOTH VatInvoice2 and VatDistributionInvoice for the last number
        $lastVatInvoice = VatInvoice2::where('business_id', $business_id)->where('prefix', $prefix_id)->orderBy('id', 'desc')->first();
        $lastDistInvoice = VatDistributionInvoice::where('business_id', $business_id)
            ->where('invoice_no', 'like', $prefixes->prefix . '-%')
            ->orderBy('id', 'desc')
            ->first();
        
        $starting_no_string = (string) $prefixes->starting_no;
        $starting_no_numeric = (int) $starting_no_string;
        $pad_length = max(strlen($starting_no_string), 1);
        
        $last_no = 0;
        if ($lastVatInvoice) {
            $curr_arr = explode('-', $lastVatInvoice->customer_bill_no);
            $last_no = max($last_no, (int) end($curr_arr));
        }
        if ($lastDistInvoice && $lastDistInvoice->invoice_no) {
            $curr_arr = explode('-', $lastDistInvoice->invoice_no);
            $last_no = max($last_no, (int) end($curr_arr));
        }

        $next_no = $last_no >= $starting_no_numeric ? ($last_no + 1) : $starting_no_numeric;
        $next_no_padded = str_pad((string) $next_no, $pad_length, '0', STR_PAD_LEFT);
        
        return $prefixes->prefix . "-" . $next_no_padded;
    }

    private function createAccountingEntries($invoice)
    {
        $business_id = $invoice->business_id;
        
        // ========== CREATE TRANSACTION RECORD ==========
        $existingTransaction = \Modules\Distribution\Entities\Core\Transaction::where('invoice_no', $invoice->invoice_no)
            ->where('business_id', $business_id)
            ->first();
        
        if (!$existingTransaction) {
            $transaction = \Modules\Distribution\Entities\Core\Transaction::create([
                'business_id' => $business_id,
                'type' => 'sell',
                'sub_type' => 'dis_invoice',
                'status' => 'final',
                'contact_id' => $invoice->customer_id,
                'transaction_date' => $invoice->date,
                'final_total' => $invoice->grand_total,
                'invoice_no' => $invoice->invoice_no,
                'created_by' => auth()->user()->id ?? 1,
                'payment_status' => $invoice->payment_credit > 0 ? 'due' : 'paid',
                'location_id' => null,
            ]);
            $transaction_id = $transaction->id;
            
            Log::info('Created transaction for VAT distribution invoice', [
                'transaction_id' => $transaction_id,
                'invoice_no' => $invoice->invoice_no,
                'contact_id' => $invoice->customer_id
            ]);
        } else {
            $existingTransaction->update([
                'final_total' => $invoice->grand_total,
                'payment_status' => $invoice->payment_credit > 0 ? 'due' : 'paid',
            ]);
            $transaction_id = $existingTransaction->id;
            
            // Delete old contact ledger and account transactions to recreate them
            DistributionContactLedger::where('transaction_id', $transaction_id)->delete();
            \Modules\Distribution\Entities\Core\AccountTransaction::where('transaction_id', $transaction_id)->delete();
        }
        
        // ========== CREATE CONTACT LEDGER FOR CREDIT SALES ==========
        if ($invoice->payment_credit > 0) {
            DistributionContactLedger::create([
                'contact_id' => $invoice->customer_id,
                'transaction_id' => $transaction_id,
                'amount' => $invoice->payment_credit,
                'type' => 'debit',
                'business_id' => $business_id,
                'created_by' => auth()->user()->id ?? 1,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}",
            ]);
        }
        
        // Get account IDs
        $finished_goods_account = $this->getAccountId($business_id, 'Finished Goods Account');
        $cogs_account = $this->getAccountId($business_id, 'COGS lubricant account book');
        $sales_account = $this->getAccountId($business_id, 'Sales lubricant account book');
        
        $cash_account = $this->getAccountId($business_id, 'Cash');
        $card_account = $this->getAccountId($business_id, 'Cards (Credit Debit) Account');
        $cheque_account = $this->getAccountId($business_id, 'Cheques in Hand');
        
        // 1. Finished Goods Account - CREDIT
        if ($finished_goods_account) {
            $this->createAccountTransaction([
                'account_id' => $finished_goods_account,
                'amount' => $invoice->total,
                'type' => 'credit',
                'sub_type' => 'dis_invoice',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
        
        // 2. COGS Account - DEBIT
        if ($cogs_account) {
            $this->createAccountTransaction([
                'account_id' => $cogs_account,
                'amount' => $this->calculateCOGS($invoice),
                'type' => 'debit',
                'sub_type' => 'dis_invoice',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
        
        // 3. Sales Account - CREDIT
        if ($sales_account) {
            $this->createAccountTransaction([
                'account_id' => $sales_account,
                'amount' => $invoice->grand_total,
                'type' => 'credit',
                'sub_type' => 'dis_invoice',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
        
        // 4. Payment Accounts - DEBIT
        if ($invoice->payment_cash > 0 && $cash_account) {
            $this->createAccountTransaction([
                'account_id' => $cash_account,
                'amount' => $invoice->payment_cash,
                'type' => 'debit',
                'sub_type' => 'dis_invoice_payment',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
        
        if ($invoice->payment_card > 0 && $card_account) {
            $this->createAccountTransaction([
                'account_id' => $card_account,
                'amount' => $invoice->payment_card,
                'type' => 'debit',
                'sub_type' => 'dis_invoice_payment',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
        
        if ($invoice->payment_credit > 0) {
            $ar_account = $this->getAccountId($business_id, 'Accounts Receivable');
            if ($ar_account) {
                $this->createAccountTransaction([
                    'account_id' => $ar_account,
                    'amount' => $invoice->payment_credit,
                    'type' => 'debit',
                    'sub_type' => 'dis_invoice_credit',
                    'operation_date' => $invoice->date,
                    'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                    'transaction_id' => $transaction_id,
                ]);
            }
        }
        
        if ($invoice->payment_cheque > 0 && $cheque_account) {
            $this->createAccountTransaction([
                'account_id' => $cheque_account,
                'amount' => $invoice->payment_cheque,
                'type' => 'debit',
                'sub_type' => 'dis_invoice_payment',
                'operation_date' => $invoice->date,
                'note' => "VAT Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,
            ]);
        }
    }

    private function getAccountId($business_id, $accountName)
    {
        $account = \Modules\Distribution\Entities\Core\Account::where('business_id', $business_id)
            ->where('name', $accountName)
            ->first();
        return $account ? $account->id : null;
    }

    private function calculateCOGS($invoice)
    {
        $totalCOGS = 0;
        foreach ($invoice->lines as $line) {
            $product = \Modules\Distribution\Entities\Core\Product::find($line->product_id);
            if ($product) {
                $lastPurchasePrice = \Modules\Distribution\Entities\Core\PurchaseLine::where('product_id', $line->product_id)
                    ->orderBy('id', 'desc')
                    ->value('purchase_price');
                
                if ($lastPurchasePrice) {
                    $totalCOGS += $line->qty * $lastPurchasePrice;
                } else {
                    // Fallback to purchase_price on product table or unit_price
                    $totalCOGS += $line->qty * ($product->purchase_price ?? 0);
                }
            }
        }
        return $totalCOGS ?: $invoice->total; // fallback to total if no purchase lines
    }

    private function createAccountTransaction($data)
    {
        return \Modules\Distribution\Entities\Core\AccountTransaction::create([
            'business_id' => $data['business_id'] ?? session()->get('user.business_id'),
            'account_id' => $data['account_id'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'sub_type' => $data['sub_type'],
            'operation_date' => $data['operation_date'],
            'note' => $data['note'],
            'transaction_id' => $data['transaction_id'],
            'created_by' => auth()->user()->id ?? 1,
        ]);
    }
}
