<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Entities\Core\Contact;
use Modules\Distribution\Entities\Core\Customer;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\SalesAgent;
use Modules\Distribution\Entities\Core\TaxRate;
use Modules\Distribution\Entities\Core\User;
use Modules\Distribution\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Modules\Distribution\Entities\DistributionInvoice;
use Modules\Distribution\Entities\DistributionInvoiceCheque;
use Modules\Distribution\Entities\DistributionInvoiceLine;
use Modules\Distribution\Entities\DistributionSalesOrder;
use Modules\Distribution\Entities\DistributionContactLedger;
use Modules\Distribution\Entities\Distribution_discount_product;
use Modules\Distribution\Entities\DistributionFreeIssue;

class DistributionInvoiceController extends Controller
{
    /**
     * @var ModuleUtil
     */
    protected $moduleUtil;

    /**
     * Constructor
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $query = DistributionInvoice::where('business_id', $business_id);
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

        $invoices = $query
            ->with(['customer', 'addedUser', 'updatedUser', 'salesOrder'])
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

        $activityPayloads = $this->getDistributionActivityPayloads(
            $invoices->getCollection()->pluck('id')->all(),
            DistributionInvoice::class
        );

        $invoices->getCollection()->transform(function ($inv) use ($transactionByInvoiceNo, $paymentByTransactionId, $activityPayloads) {
            $inv->linked_transaction_id = $transactionByInvoiceNo[$inv->invoice_no] ?? null;
            $inv->linked_payment_id = !empty($inv->linked_transaction_id)
                ? ($paymentByTransactionId[$inv->linked_transaction_id] ?? null)
                : null;
            $inv->activity_payloads = $activityPayloads[$inv->id] ?? $this->fallbackInvoiceActivityPayloads($inv);
            return $inv;
        });

        $users = \Modules\Distribution\Entities\Core\User::where('business_id', $business_id)->orderBy('username')->pluck('username', 'id');
        $invoiceNos = DistributionInvoice::where('business_id', $business_id)
            ->whereNotNull('invoice_no')
            ->orderBy('invoice_no')
            ->pluck('invoice_no', 'invoice_no');
        $customers = DistributionInvoice::where('business_id', $business_id)
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
        $locations = DistributionInvoice::where('business_id', $business_id)
            ->whereNotNull('customer_address')
            ->where('customer_address', '!=', '')
            ->orderBy('customer_address')
            ->pluck('customer_address', 'customer_address');

        return view('distribution::invoices.index')->with(compact('invoices', 'users', 'invoiceNos', 'customers', 'locations'));
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $business = Business::find($business_id);
        $invoice = DistributionInvoice::where('business_id', $business_id)
            ->with(['customer', 'lines.product', 'lines.product.unit', 'salesRep', 'route', 'vehicle', 'category', 'cheques'])
            ->findOrFail($id);
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->first();
        if (!$location) {
            $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();
        }

        return view('distribution::invoices.show')->with(compact('invoice', 'business', 'location'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        // Get business details
        $business = Business::find($business_id);

        // Get the first/default business location for address and contact
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)
            ->first();

        // If no active location, get any location
        if (!$location) {
            $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();
        }

        // Fetch customers the same way as Contact -> Customers
        $customers = Contact::where('business_id', $business_id)
            ->whereIn('is_property', [1, 0])
            ->where(function ($q) {
                $q->where('type', 'customer')
                    ->orWhere('type', 'both');
            })
            ->whereNull('deleted_at') // Exclude soft-deleted contacts
            ->get()
            ->filter(function ($customer) {
                // Only include customers with valid IDs
                return !empty($customer->id);
            })
            ->map(function ($customer) {
                $name = $customer->name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
                // Fallback if name is still empty
                if (empty($name)) {
                    $name = $customer->mobile ?? $customer->landline ?? 'Customer #' . $customer->id;
                }
                return [
                    'id' => $customer->id,
                    'name' => $name,
                    'address' => $customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '',
                    'phone' => $customer->mobile ?? $customer->landline ?? '',
                ];
            })
            ->values(); // Re-index the array

        // Get sales reps from Sales Agents table
        $salesReps = SalesAgent::forBusiness($business_id)
            ->orderBy('name', 'asc')
            ->pluck('name', 'id')
            ->toArray();
        $default_sales_rep_id = SalesAgent::where('business_id', $business_id)
            ->where('user_id', auth()->id())
            ->value('id');

        $routes    = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles  = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');

        // Get main categories only
        $categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');

        // Get precision settings
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;

        // Check Distribution permissions
        $show_date_picker = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_show_date_picker');
        $auto_date_time = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');


        // CORRECT - read the actual Yes/No values from package_details
        $subscription = \Modules\Distribution\Entities\Integration\Subscription::active_subscription($business_id);
        $package_details = !empty($subscription) ? json_decode(json_encode($subscription->package_details), true) : [];

        $module_enable = [
            'distribution_free'         => $package_details['distribution_free'] ?? 'No',
            'distribution_free_bottles' => $package_details['distribution_free_bottles'] ?? 'No',
        ];

        // Get tax rates for dropdown
        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        // Generate the next invoice number for display (non-mutating)
        $invoice_no = $this->peekNextInvoiceNumber();
        $salesOrder = null;
        if (request()->filled('sales_order_id')) {
            $salesOrder = DistributionSalesOrder::where('business_id', $business_id)
                ->with('lines.product')
                ->find(request()->sales_order_id);
        }

        return view('distribution::invoices.create', compact(
            'business',
            'location',
            'customers',
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'show_date_picker',
            'auto_date_time',
            'currency_precision',
            'quantity_precision',
            'taxes',
            'tax_attributes',
            'module_enable',
            'invoice_no',
            'salesOrder',
            'default_sales_rep_id'
        ));
    }

    /**
     * Returns matching free issue rows for a given product/qty (called via AJAX).
     */

    /**
     * Returns matching free issue rows for a given product/qty (called via AJAX).
     * Logic: Free Qty = floor(purchase_qty / qty_from) × free_qty
     * Example: Qty From: 3, Free Qty: 1
     *   Buy 3 → 1 free
     *   Buy 6 → 2 free
     *   Buy 9 → 3 free
     */


    public function getFreeIssues(Request $request)
{
    $business_id = session()->get('user.business_id');
    $product_id  = (int) $request->input('product_id');
    $qty         = (float) $request->input('qty', 0);
    $now         = now();

    $freeIssues = DistributionFreeIssue::where('business_id', $business_id)
        ->where('product_name', $product_id)
        ->where('status', 1)
        ->where(function ($q) use ($now) {
            $q->whereNull('date_since')->orWhere('date_since', '<=', $now);
        })
        ->where(function ($q) use ($now) {
            $q->whereNull('date_till')->orWhere('date_till', '>=', $now);
        })
        ->get(['id', 'qty_from', 'qty_till', 'free_qty', 'free_products', 'is_free', 'is_free_bottles', 'qty_type']);

    $groupedResults = [];

    foreach ($freeIssues as $fi) {
        $totalFreeQty = 0;
        
        // Formula: floor(Sale Qty / qty_from) × free_qty
        if ($fi->qty_from > 0 && $qty >= $fi->qty_from) {
            $totalFreeQty = floor($qty / $fi->qty_from) * $fi->free_qty;
        }
        
        if ($totalFreeQty <= 0) {
            continue;
        }
        
        $freeProductIds = is_array($fi->free_products)
            ? $fi->free_products
            : json_decode($fi->free_products ?? '[]', true);
        
        foreach ((array) $freeProductIds as $fpid) {
            if (!isset($groupedResults[$fpid])) {
                $groupedResults[$fpid] = [
                    'product_id'   => $fpid,
                    'free_qty'     => 0,
                    'is_free'      => $fi->is_free,
                    'is_free_bottles' => $fi->is_free_bottles,
                ];
            }
            $groupedResults[$fpid]['free_qty'] += $totalFreeQty;
        }
    }
    
    $results = [];
    foreach ($groupedResults as $fpid => $data) {
        $fp = DB::table('products')->where('id', $fpid)->first(['id', 'name']);
        if ($fp) {
            $results[] = [
                'product_id'   => $fp->id,
                'product_name' => $fp->name,
                'free_qty'     => $data['free_qty'],
                'is_free'      => $data['is_free'],
                'is_free_bottles' => $data['is_free_bottles'],
            ];
        }
    }
    
    return response()->json($results);
}

    public function store(Request $request)
    {
        Log::info('invoice request received in store: ', $request->all());
        $business_id = session()->get('user.business_id');



// Validate single product category - exclude free items from validation
if ($request->filled('category_id')) {
    $category_id = $request->category_id;
    $product_ids = $request->product_id ?? [];
    $is_free_auto = $request->is_free_auto ?? [];
    $is_free = $request->is_free ?? [];
    $is_free_bottles = $request->is_free_bottles ?? [];
    
    // Get unique product IDs with their free status
    $regular_product_ids = [];
    
    foreach ($product_ids as $index => $pid) {
        // Check if this is a free item
        $isFreeItem = false;
        if (isset($is_free_auto[$index]) && $is_free_auto[$index] == 1) {
            $isFreeItem = true;
        }
        if (isset($is_free[$index]) && $is_free[$index] == 1) {
            $isFreeItem = true;
        }
        if (isset($is_free_bottles[$index]) && $is_free_bottles[$index] == 1) {
            $isFreeItem = true;
        }
        
        // Only add to regular products if NOT a free item
        if (!$isFreeItem) {
            $regular_product_ids[] = $pid;
        }
    }
    
    // Remove duplicates
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

        // Combine date and time if separate
        // Check if date already contains time (from JavaScript combination)
        $dateTime = $request->date;

        Log::info('Invoice store - date input: ' . $dateTime);
        Log::info('Invoice store - all request data: ', $request->all());

        // If date already contains time (has space and colon), use it as is
        if (strpos($dateTime, ' ') !== false && strpos($dateTime, ':') !== false) {
            // Date already combined, parse it properly without timezone conversion
            $parts = explode(' ', $dateTime);
            $datePart = $parts[0] ?? '';
            $timePart = trim($parts[1] ?? '');

            // Use the time part as-is from the combined value - don't override it
            // Only ensure it has seconds format
            if ($timePart && strlen($timePart) == 5) { // HH:MM format
                $timePart .= ':00';
            } elseif (empty($timePart)) {
                // Only default to 00:00:00 if truly empty
                $timePart = '00:00:00';
            }

            // Use the date and time as-is without strtotime to avoid timezone issues
            $dateTime = $datePart . ' ' . $timePart;
            Log::info('Using combined date/time (from JS): ' . $dateTime);
        } elseif ($request->has('time') && $request->time) {
            // Extract just the date part (use as-is, don't parse with strtotime)
            $datePart = $request->date;
            // If date contains time, extract just the date part
            if (strpos($datePart, ' ') !== false) {
                $datePart = explode(' ', $datePart)[0];
            }

            $timePart = $request->time;
            // Ensure time has seconds
            if (strlen($timePart) == 5) { // HH:MM format
                $timePart .= ':00';
            }
            $dateTime = $datePart . ' ' . $timePart;
            Log::info('Combined separate date/time: ' . $dateTime);
        } else {
            // If no time provided, use date only and set to current time (not 00:00)
            $datePart = $request->date;
            // If date contains time, extract just the date part
            if (strpos($datePart, ' ') !== false) {
                $datePart = explode(' ', $datePart)[0];
            }
            // Use current time instead of 00:00
            $currentTime = date('H:i:s');
            $dateTime = $datePart . ' ' . $currentTime;
            Log::info('Using date with current time: ' . $dateTime);
        }

        // Get customer details from contacts table
        $customer = Contact::find($request->customer_id);
        $customerName = '';
        $customerAddress = '';
        $customerContact = '';

        if ($customer) {
            $customerName = $customer->name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $customerAddress = $customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '';
            $customerContact = $customer->mobile ?? $customer->landline ?? '';
        }

        $paymentCash   = (float) $request->input('payment_cash', 0);
        $paymentCard   = (float) $request->input('payment_card', 0);
        $paymentCredit = (float) $request->input('payment_credit', 0);
        $paymentCheque = (float) $request->input('payment_cheque', 0);
        $paymentTotal  = $paymentCash + $paymentCard + $paymentCredit + $paymentCheque;

        $invoice = DistributionInvoice::create([
            'business_id'      => $business_id,
            'customer_id'      => $request->customer_id,
            'customer_name'    => $customerName,
            'customer_address' => $customerAddress,
            'customer_contact' => $customerContact,
            'date'             => $dateTime,
            'delivery_date'    => $request->delivery_date ?: null,
            'sales_rep_id'     => $request->sales_rep_id,
            'route_id'         => $request->route_id,
            'vehicle_id'       => $request->vehicle_id,
            'category_id'      => $request->category_id,
            'invoice_no'       => $this->generateInvoiceNumber(),
            'loading_sheet_no' => $request->loading_sheet_no ?? null,
            'invoice_note'     => $request->invoice_note,
            'shipping_note'    => $request->shipping_note,
            'sales_order_note' => $request->sales_order_note,
            'shipping_details' => $request->shipping_details,
            'shipping_status'  => $request->shipping_status ?: 'ordered',
            'status'           => $request->status ?: 'active',
            'sales_order_id'   => $request->sales_order_id ?: null,
            'added_by'         => auth()->id(),
            'updated_by'       => auth()->id(),
            'total'            => 0,
            'discount'         => 0,
            'grand_total'      => 0,
            'payment_cash'     => $paymentCash,
            'payment_card'     => $paymentCard,
            'payment_credit'   => $paymentCredit,
            'payment_cheque'   => $paymentCheque,
            'payment_total'    => $paymentTotal,
        ]);

        $total         = 0;
        $discountTotal = 0;

        foreach ($request->product_id as $i => $pid) {
            // Check if this is a free item
            $isFree = (int) ($request->is_free[$i] ?? 0);
            $isFreeBottles = (int) ($request->is_free_bottles[$i] ?? 0);
            $isFreeAuto = (int) ($request->is_free_auto[$i] ?? 0);
            $isFreeItem = $isFree || $isFreeBottles || $isFreeAuto;

            // Get basic line data
            $qty = $request->qty[$i];
            $unit = $request->unit_price[$i];

            // FIXED: Calculate amount properly - don't trust the request value
            if ($isFreeItem) {
                $amount = 0;
            } else {
                $amount = $qty * $unit;
            }

            // Get discount info
            $userDiscount = $request->discount[$i] ?? 0;
            $discountType = $request->discount_type[$i] ?? 'fixed';

            // Calculate discount amount
            $discountAmount = 0;
            if (!$isFreeItem) {
                // Only calculate discount for non-free items
                $discountInfo = $this->getProductMaxDiscount($pid);
                $maxAllowed = $discountInfo['max_discount'];

                if ($discountType === 'percentage') {
                    $discountAmount = ($amount * $userDiscount) / 100;

                    // Validate percentage discount
                    if ($maxAllowed !== null && $userDiscount > $maxAllowed) {
                        DB::rollBack();
                        return back()->withErrors([
                            'discount' => "Discount percentage for product ID {$pid} cannot exceed {$maxAllowed}%",
                        ]);
                    }
                } else {
                    $discountAmount = $userDiscount;

                    // Validate fixed discount
                    if ($maxAllowed !== null && $discountAmount > $maxAllowed) {
                        DB::rollBack();
                        return back()->withErrors([
                            'discount' => "Discount amount for product ID {$pid} cannot exceed {$maxAllowed}",
                        ]);
                    }
                }
            }

            $finalAmount = $request->final_amount[$i] ?? ($amount - $discountAmount);

            // Create the line
            DistributionInvoiceLine::create([
                'invoice_id'      => $invoice->id,
                'product_id'      => $pid,
                'unit_id'         => $request->unit_id[$i] ?? null,
                'qty'             => $qty,
                'unit_price'      => $unit,
                'amount'          => $amount,
                'discount'        => $discountAmount,
                'final_amount'    => $finalAmount,
                'is_free'         => $isFree,
                'is_free_bottles' => $isFreeBottles,
                'is_free_auto'    => $isFreeAuto,
            ]);

            // Only add to totals if NOT a free item
            if (!$isFreeItem) {
                $total += $amount;
                $discountTotal += $discountAmount;
            }
        }

        $grandTotal = $request->grand_total ?? ($total - $discountTotal);

        $invoice->update([
            'total'         => $total,
            'discount'      => $discountTotal,
            'grand_total'   => $grandTotal,
            'payment_cash'  => $paymentCash,
            'payment_card'  => $paymentCard,
            'payment_credit' => $paymentCredit,
            'payment_cheque' => $paymentCheque,
            'payment_total' => $paymentTotal,
        ]);

        if (!empty($invoice->sales_order_id)) {
            $salesOrder = DistributionSalesOrder::where('business_id', $business_id)
                ->find($invoice->sales_order_id);
            if ($salesOrder) {
                $salesOrder->status = 'created_invoice_no ' . $invoice->invoice_no;
                $salesOrder->save();
            }
        }

        $chequeBanks   = $request->input('cheque_bank', []);
        $chequeBranchs = $request->input('cheque_branch', []);
        $chequeNos     = $request->input('cheque_no', []);
        $chequeDates   = $request->input('cheque_date', []);
        $chequeAmounts = $request->input('cheque_amount', []);

        if (!empty($chequeAmounts)) {
            foreach ($chequeAmounts as $i => $amount) {
                $amount = (float) $amount;
                if ($amount <= 0) {
                    continue;
                }

                DistributionInvoiceCheque::create([
                    'invoice_id'  => $invoice->id,
                    'bank'        => $chequeBanks[$i]   ?? null,
                    'branch'      => $chequeBranchs[$i] ?? null,
                    'cheque_no'   => $chequeNos[$i]     ?? null,
                    'cheque_date' => $chequeDates[$i]   ?? null,
                    'amount'      => $amount,
                ]);
            }
        }

                $this->createAccountingEntries($invoice);


        DB::commit();

        return redirect()->route('distribution.list_invoices.index')
            ->with('status', 'Invoice created successfully!');
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $invoice = DistributionInvoice::where('business_id', $business_id)
            ->with(['lines.product', 'lines.product.unit', 'cheques'])
            ->findOrFail($id);

        $business = Business::find($business_id);

        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
            ->where('is_active', 1)->first();
        if (!$location) {
            $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();
        }

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('is_property', [1, 0])
            ->where(function ($q) {
                $q->where('type', 'customer')->orWhere('type', 'both');
            })
            ->whereNull('deleted_at')
            ->get()
            ->filter(fn($c) => !empty($c->id))
            ->map(function ($c) {
                $name = $c->name ?? trim(($c->first_name ?? '') . ' ' . ($c->last_name ?? ''));
                if (empty($name)) {
                    $name = $c->mobile ?? $c->landline ?? 'Customer #' . $c->id;
                }
                return [
                    'id'      => $c->id,
                    'name'    => $name,
                    'address' => $c->landmark ?? $c->address_line_1 ?? $c->address ?? '',
                    'phone'   => $c->mobile ?? $c->landline ?? '',
                ];
            })->values();

        $salesReps  = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id')->toArray();
        $routes     = DB::table('distribution_routes')->where('business_id', $business_id)->pluck('name', 'id');
        $vehicles   = DB::table('distribution_vehicles')->where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories = Category::where('business_id', $business_id)->where('parent_id', 0)->pluck('name', 'id');

        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;

        $free_issue_toggle   = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_free_issue_toggle');
        $free_bottles_toggle = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_free_bottles_toggle');
        $module_enable = [
            'distribution_free_issue_toggle'   => $free_issue_toggle,
            'distribution_free_bottles_toggle' => $free_bottles_toggle,
        ];

        $tax_dropdown   = TaxRate::forBusinessDropdown($business_id, true, true);
        $taxes          = $tax_dropdown['tax_rates'];
        $tax_attributes = $tax_dropdown['attributes'];

        return view('distribution::invoices.edit', compact(
            'invoice',
            'business',
            'location',
            'customers',
            'salesReps',
            'routes',
            'vehicles',
            'categories',
            'currency_precision',
            'quantity_precision',
            'taxes',
            'tax_attributes',
            'module_enable'
        ));
    }

    /**
     * Update the specified invoice in storage.
     */
    public function update(Request $request, $id)
    {
        $business_id = session()->get('user.business_id');

        $invoice = DistributionInvoice::where('business_id', $business_id)->findOrFail($id);

        DB::beginTransaction();

        // Parse date/time the same way as store()
        $dateTime = $request->date;
        if (strpos($dateTime, ' ') !== false && strpos($dateTime, ':') !== false) {
            $parts    = explode(' ', $dateTime);
            $datePart = $parts[0] ?? '';
            $timePart = trim($parts[1] ?? '');
            if ($timePart && strlen($timePart) == 5) {
                $timePart .= ':00';
            } elseif (empty($timePart)) {
                $timePart = '00:00:00';
            }
            $dateTime = $datePart . ' ' . $timePart;
        } elseif ($request->has('time') && $request->time) {
            $datePart = explode(' ', $request->date)[0];
            $timePart = $request->time;
            if (strlen($timePart) == 5) {
                $timePart .= ':00';
            }
            $dateTime = $datePart . ' ' . $timePart;
        } else {
            $datePart = explode(' ', $request->date)[0];
            $dateTime = $datePart . ' ' . date('H:i:s');
        }

        // Resolve customer details
        $customer        = Contact::find($request->customer_id);
        $customerName    = '';
        $customerAddress = '';
        $customerContact = '';
        if ($customer) {
            $customerName    = $customer->name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
            $customerAddress = $customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '';
            $customerContact = $customer->mobile ?? $customer->landline ?? '';
        }

        $paymentCash   = (float) $request->input('payment_cash', 0);
        $paymentCard   = (float) $request->input('payment_card', 0);
        $paymentCredit = (float) $request->input('payment_credit', 0);
        $paymentCheque = (float) $request->input('payment_cheque', 0);
        $paymentTotal  = $paymentCash + $paymentCard + $paymentCredit + $paymentCheque;

        // Update invoice header
        $invoice->update([
            'customer_id'      => $request->customer_id,
            'customer_name'    => $customerName,
            'customer_address' => $customerAddress,
            'customer_contact' => $customerContact,
            'date'             => $dateTime,
            'delivery_date'    => $request->delivery_date ?: null,
            'sales_rep_id'     => $request->sales_rep_id,
            'route_id'         => $request->route_id,
            'vehicle_id'       => $request->vehicle_id,
            'category_id'      => $request->category_id,
            'loading_sheet_no' => $request->loading_sheet_no ?? null,
            'invoice_note'     => $request->invoice_note,
            'shipping_note'    => $request->shipping_note,
            'sales_order_note' => $request->sales_order_note,
            'shipping_details' => $request->shipping_details,
            'shipping_status'  => $request->shipping_status ?: 'ordered',
            'status'           => $request->status ?: 'active',
            'updated_by'       => auth()->id(),
            'payment_cash'     => $paymentCash,
            'payment_card'     => $paymentCard,
            'payment_credit'   => $paymentCredit,
            'payment_cheque'   => $paymentCheque,
            'payment_total'    => $paymentTotal,
        ]);

        // Delete old lines and cheques, then re-create
        $invoice->lines()->delete();
        $invoice->cheques()->delete();

        $productIds  = $request->product_id ?? [];
        $total       = 0;
        $discountTotal = 0;

        foreach ($productIds as $i => $pid) {
            if (empty($pid)) continue;

            $qty        = (float) ($request->qty[$i] ?? 1);
            $unit       = (float) ($request->unit_price[$i] ?? 0);
            $amount     = (float) ($request->amount[$i] ?? ($qty * $unit));
            $discountRaw  = $request->discount[$i] ?? 0;
            $discountType = $request->discount_type[$i] ?? 'fixed';

            if ($discountType === 'percentage') {
                $discountAmount = $amount * ((float) $discountRaw / 100);
            } else {
                $discountAmount = (float) $discountRaw;
            }

            $finalAmount = $request->final_amount[$i] ?? ($amount - $discountAmount);

            DistributionInvoiceLine::create([
                'invoice_id'      => $invoice->id,
                'product_id'      => $pid,
                'unit_id'         => $request->unit_id[$i] ?? null,
                'qty'             => $qty,
                'unit_price'      => $unit,
                'amount'          => $amount,
                'discount'        => $discountAmount,
                'final_amount'    => $finalAmount,
                'is_free'         => (int) ($request->is_free[$i] ?? 0),
                'is_free_bottles' => (int) ($request->is_free_bottles[$i] ?? 0),
                'is_free_auto'    => (int) ($request->is_free_auto[$i] ?? 0),
            ]);

            $total         += $amount;
            $discountTotal += $discountAmount;
        }

        $grandTotal = $request->grand_total ?? ($total - $discountTotal);
        $invoice->update([
            'total'       => $total,
            'discount'    => $discountTotal,
            'grand_total' => $grandTotal,
        ]);

        // Re-save cheques
        $chequeBanks   = $request->input('cheque_bank', []);
        $chequeBranchs = $request->input('cheque_branch', []);
        $chequeNos     = $request->input('cheque_no', []);
        $chequeDates   = $request->input('cheque_date', []);
        $chequeAmounts = $request->input('cheque_amount', []);

        foreach ($chequeAmounts as $i => $chequeAmt) {
            $chequeAmt = (float) $chequeAmt;
            if ($chequeAmt <= 0) continue;
            DistributionInvoiceCheque::create([
                'invoice_id'  => $invoice->id,
                'bank'        => $chequeBanks[$i]   ?? null,
                'branch'      => $chequeBranchs[$i] ?? null,
                'cheque_no'   => $chequeNos[$i]     ?? null,
                'cheque_date' => $chequeDates[$i]   ?? null,
                'amount'      => $chequeAmt,
            ]);
        }

               \Modules\Distribution\Entities\Core\AccountTransaction::where('transaction_id', $invoice->id)
            ->whereIn('sub_type', ['dis_invoice', 'dis_invoice_payment', 'dis_invoice_credit'])
            ->delete();
        
        $this->createAccountingEntries($invoice);

        DB::commit();

        return redirect()->route('distribution.list_invoices.index')
            ->with('status', 'Invoice updated successfully!');
    }

    /**
     * Preview the next invoice number without saving/incrementing.
     * Used in create() to display the number to the user before submission.
     */
    private function peekNextInvoiceNumber()
    {
        $business_id = session()->get('user.business_id');

        $setting = DB::table('distribution_prefix_settings')
            ->where('business_id', $business_id)
            ->where('numbering_type', 'sales_invoice')
            ->first();

        if (! $setting) {
            // Fallback: look at last saved invoice number
            $lastInvoice = DistributionInvoice::where('business_id', $business_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastInvoice && $lastInvoice->invoice_no) {
                preg_match('/\d+$/', $lastInvoice->invoice_no, $matches);
                $nextNumber = !empty($matches) ? ((int) $matches[0] + 1) : 1;
            } else {
                $nextNumber = 1;
            }

            $invoice_no = (string) $nextNumber;
            while (DistributionInvoice::where('business_id', $business_id)
                ->where('invoice_no', $invoice_no)->exists()
            ) {
                $nextNumber++;
                $invoice_no = (string) $nextNumber;
            }

            return $invoice_no;
        }

        $current = $setting->current_no ?? $setting->starting_no ?? 1;
        $prefix  = $setting->prefix ?? '';
        $invoice_no = $prefix . $current;

        // Skip duplicates
        while (DistributionInvoice::where('business_id', $business_id)
            ->where('invoice_no', $invoice_no)->exists()
        ) {
            $current++;
            $invoice_no = $prefix . $current;
        }

        // Do NOT save — just return the preview number
        return $invoice_no;
    }

    private function generateInvoiceNumber()
    {
        $business_id = session()->get('user.business_id');

        $setting = DB::table('distribution_prefix_settings')
            ->where('business_id', $business_id)
            ->where('numbering_type', 'sales_invoice')
            ->first();

        /*
        |--------------------------------------------------
        | CASE 1: Prefix settings NOT found
        |--------------------------------------------------
        */
        if (! $setting) {

            // Fallback: generate number based on last invoice from distribution_invoices table
            $lastInvoice = DistributionInvoice::where('business_id', $business_id)
                ->orderBy('id', 'desc')
                ->first();

            if ($lastInvoice && $lastInvoice->invoice_no) {
                // Extract numeric part from last invoice number
                $lastInvoiceNo = $lastInvoice->invoice_no;
                // Try to extract number from invoice_no (handle cases like "INV-001" or just "1")
                preg_match('/\d+$/', $lastInvoiceNo, $matches);
                $nextNumber = !empty($matches) ? ((int) $matches[0] + 1) : 1;
            } else {
                $nextNumber = 1;
            }

            // Check for duplicates and increment if needed
            $invoice_no = (string) $nextNumber;
            while (DistributionInvoice::where('business_id', $business_id)
                ->where('invoice_no', $invoice_no)
                ->exists()
            ) {
                $nextNumber++;
                $invoice_no = (string) $nextNumber;
            }

            return $invoice_no;
        }

        /*
        |--------------------------------------------------
        | CASE 2: Prefix settings exist
        |--------------------------------------------------
        */

        $current = $setting->current_no ?? $setting->starting_no ?? 1;

        $prefix = $setting->prefix ?? '';

        $invoice_no = $prefix . $current;

        // Check for duplicates and increment if needed
        while (DistributionInvoice::where('business_id', $business_id)
            ->where('invoice_no', $invoice_no)
            ->exists()
        ) {
            $current++;
            $invoice_no = $prefix . $current;
        }

        // Increment for next time
        DB::table('distribution_prefix_settings')
            ->where('id', $setting->id)
            ->update([
                'current_no' => $current + 1,
            ]);

        return $invoice_no;
    }

    public function getProductPrice(Request $request)
    {
        $product     = Product::with('units')->find($request->product_id);
        $discountInfo = $this->getProductMaxDiscount($request->product_id);

        return response()->json([
            'unit_price'   => $product->sell_price ?? 0,
            'max_discount' => $discountInfo['max_discount'],
            'discount_type' => $discountInfo['discount_type'],
            'units'        => $product->units->map(fn($u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function getProductsByCategory(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $query = Product::where('business_id', $business_id)
            ->where(function ($q) {
                // Include active products (is_inactive = 0 or null)
                $q->where('is_inactive', 0)
                    ->orWhereNull('is_inactive');
            })
            ->where(function ($q) {
                // Include products for selling (not_for_selling = 0 or null)
                $q->where('not_for_selling', 0)
                    ->orWhereNull('not_for_selling');
            });

        if ($request->category_id && $request->category_id != '') {
            // Include products from the selected category OR its subcategories
            $query->where(function ($q) use ($request) {
                $q->where('category_id', $request->category_id)
                    ->orWhere('sub_category_id', $request->category_id);
            });
        }

        // Add product type filter if provided
        if ($request->type && $request->type != '') {
            $query->where('type', $request->type);
        }

        // Order by latest first (id DESC - newest products appear first)
        $products = $query->orderBy('id', 'DESC')
            ->select('id', 'name', 'category_id', 'sub_category_id', 'type')
            ->get();

        return response()->json($products);
    }

    private function getProductMaxDiscount($productId)
    {
        $business_id = request()->session()->get('user.business_id');

        // Get the latest discount for this product from distribution_discount_products
        $discount = Distribution_discount_product::select('distribution_discount_products.max_discount', 'distribution_discount_products.discount_type')
            ->join('distribution_discounts', 'distribution_discount_products.discount_id', '=', 'distribution_discounts.id')
            ->where('distribution_discount_products.product_id', $productId)
            ->where('distribution_discounts.business_id', $business_id)
            ->orderBy('distribution_discounts.date_time', 'desc')
            ->first();

        // Return null if no discount rule exists (means unlimited discount allowed)
        // Return the max_discount value if a rule exists (0 means no discount allowed, >0 means max limit)
        if (!$discount) {
            return ['max_discount' => null, 'discount_type' => null];
        }

        return [
            'max_discount' => $discount->max_discount,
            'discount_type' => $discount->discount_type ?? 'fixed'
        ];
    }

    public function customerInfo($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            if (!$business_id) {
                return response()->json(['error' => 'Business ID not found'], 400);
            }

            // Customers are stored in the contacts table
            $customer = Contact::where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            if (! $customer) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            // Get address from landmark or address field
            $address = $customer->landmark ?? $customer->address_line_1 ?? $customer->address ?? '';

            // Get contact number
            $contactNo = $customer->mobile ?? $customer->landline ?? '';

            return response()->json([
                'name'       => $customer->name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
                'address'    => $address,
                'contact_no' => $contactNo,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in customerInfo: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Error loading customer information: ' . $e->getMessage()], 500);
        }
    }

public function productInfo(Request $request)
{
    $product_id = $request->product_id;
    
    // Load product with variations - include BOTH price columns
    $product = Product::where('id', $product_id)
        ->with(['variations' => function($query) {
            $query->select('id', 'product_id', 'default_sell_price', 'sell_price_inc_tax');
        }])
        ->select('id', 'unit_id', 'name')
        ->first();

    if (! $product) {
        return response()->json([
            'unit_price'   => 0,
            'price_inc_tax' => 0,
            'max_discount' => null,
            'discount_type' => null,
            'units'        => [],
        ]);
    }

    // Get max discount from distribution discount settings
    $discountInfo = $this->getProductMaxDiscount($product_id);

    // Get prices from variation
    $defaultSellPrice = 0;
    $sellPriceIncTax = 0;
    
    if ($product->variations && $product->variations->count() > 0) {
        $variation = $product->variations->first();
        $defaultSellPrice = floatval($variation->default_sell_price ?? 0);
        $sellPriceIncTax = floatval($variation->sell_price_inc_tax ?? 0);
    }

    // Get units
    $units = [];
    if ($product->unit_id) {
        $mainUnit = DB::table('units')->find($product->unit_id);
        if ($mainUnit) {
            $units[] = [
                'id' => $mainUnit->id,
                'name' => $mainUnit->actual_name ?? $mainUnit->short_name ?? 'Unit'
            ];
        }
    }

    Log::info('Product info', [
        'product_id' => $product_id,
        'product_name' => $product->name,
        'default_sell_price' => $defaultSellPrice,
        'sell_price_inc_tax' => $sellPriceIncTax
    ]);

    return response()->json([
        'unit_price'   => $defaultSellPrice,      // For Unit Price field
        'price_inc_tax' => $sellPriceIncTax,      // For Price Inc. Tax field
        'max_discount' => $discountInfo['max_discount'],
        'discount_type' => $discountInfo['discount_type'],
        'units'        => $units,
    ]);
}

    /**
 * Print the specified invoice
 */
public function printInvoice($id)
{
    $business_id = request()->session()->get('user.business_id');
    
    $business = Business::find($business_id);
    $invoice = DistributionInvoice::where('business_id', $business_id)
        ->with(['customer', 'lines.product', 'lines.product.unit', 'salesRep', 'route', 'vehicle', 'category', 'cheques'])
        ->findOrFail($id);
    
    $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)
        ->where('is_active', 1)
        ->first();
    
    if (!$location) {
        $location = \Modules\Distribution\Entities\Core\BusinessLocation::where('business_id', $business_id)->first();
    }
    
    return view('distribution::invoices.print', compact('invoice', 'business', 'location'));
}



/**
 * Create accounting entries for distribution invoice
 */
private function createAccountingEntries($invoice)
{
    $business_id = $invoice->business_id;
    
    // ========== CREATE TRANSACTION RECORD ==========
    // Check if transaction already exists
    $existingTransaction = \Modules\Distribution\Entities\Core\Transaction::where('invoice_no', $invoice->invoice_no)
        ->where('business_id', $business_id)
        ->first();
    
    if (!$existingTransaction) {
        // Create transaction record for the ledger
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
        
        Log::info('Created transaction for distribution invoice', [
            'transaction_id' => $transaction_id,
            'invoice_no' => $invoice->invoice_no,
            'contact_id' => $invoice->customer_id
        ]);
    } else {
        $transaction_id = $existingTransaction->id;
    }
    
    // ========== CREATE CONTACT LEDGER FOR CREDIT SALES ==========
    if ($invoice->payment_credit > 0) {
        // Check if contact_ledger already exists
        $existingLedger = DistributionContactLedger::where('transaction_id', $transaction_id)
            ->where('contact_id', $invoice->customer_id)
            ->first();
        
        if (!$existingLedger) {
            DistributionContactLedger::create([
                'contact_id' => $invoice->customer_id,
                'transaction_id' => $transaction_id,
                'amount' => $invoice->payment_credit,
                'type' => 'debit',  // Debit because customer owes money
                'business_id' => $business_id,
                'created_by' => auth()->user()->id ?? 1,
                'note' => "Dis. Invoice No: {$invoice->invoice_no}",
            ]);
            
            Log::info('Created contact_ledger for distribution invoice', [
                'contact_id' => $invoice->customer_id,
                'transaction_id' => $transaction_id,
                'amount' => $invoice->payment_credit
            ]);
        }
    }
    // ========== END OF ADDED BLOCK ==========
    
    // Get account IDs
    $finished_goods_account = $this->getAccountId($business_id, 'Finished Goods Account');
    $cogs_account = $this->getAccountId($business_id, 'COGS lubricant account book');
    $sales_account = $this->getAccountId($business_id, 'Sales lubricant account book');
    
    // Payment accounts
    $cash_account = $this->getAccountId($business_id, 'Cash');
    $card_account = $this->getAccountId($business_id, 'Cards (Credit Debit) Account');
    $cheque_account = $this->getAccountId($business_id, 'Cheques in Hand');
    
    // 1. Finished Goods Account - CREDIT (for products sold)
    if ($finished_goods_account) {
        $this->createAccountTransaction([
            'account_id' => $finished_goods_account,
            'amount' => $invoice->total,
            'type' => 'credit',
            'sub_type' => 'dis_invoice',
            'operation_date' => $invoice->date,
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
    
    // 2. COGS Account - DEBIT (Cost of goods sold)
    if ($cogs_account) {
        $this->createAccountTransaction([
            'account_id' => $cogs_account,
            'amount' => $this->calculateCOGS($invoice),
            'type' => 'debit',
            'sub_type' => 'dis_invoice',
            'operation_date' => $invoice->date,
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
    
    // 3. Sales Account - CREDIT (Revenue)
    if ($sales_account) {
        $this->createAccountTransaction([
            'account_id' => $sales_account,
            'amount' => $invoice->grand_total,
            'type' => 'credit',
            'sub_type' => 'dis_invoice',
            'operation_date' => $invoice->date,
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
    
    // 4. Payment Accounts - DEBIT based on payment methods
    if ($invoice->payment_cash > 0 && $cash_account) {
        $this->createAccountTransaction([
            'account_id' => $cash_account,
            'amount' => $invoice->payment_cash,
            'type' => 'debit',
            'sub_type' => 'dis_invoice_payment',
            'operation_date' => $invoice->date,
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
    
    if ($invoice->payment_card > 0 && $card_account) {
        $this->createAccountTransaction([
            'account_id' => $card_account,
            'amount' => $invoice->payment_card,
            'type' => 'debit',
            'sub_type' => 'dis_invoice_payment',
            'operation_date' => $invoice->date,
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
    
    if ($invoice->payment_credit > 0) {
        // Credit payment goes to Accounts Receivable
        $ar_account = $this->getAccountId($business_id, 'Accounts Receivable');
        if ($ar_account) {
            $this->createAccountTransaction([
                'account_id' => $ar_account,
                'amount' => $invoice->payment_credit,
                'type' => 'debit',
                'sub_type' => 'dis_invoice_credit',
                'operation_date' => $invoice->date,
                'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
                'transaction_id' => $transaction_id,  // Use the transaction ID
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
            'note' => "Dis. Invoice No: {$invoice->invoice_no}\nCustomer Name: {$invoice->customer_name}",
            'transaction_id' => $transaction_id,  // Use the transaction ID
        ]);
    }
}

/**
 * Get account ID by name
 */
private function getAccountId($business_id, $accountName)
{
    $account = \Modules\Distribution\Entities\Core\Account::where('business_id', $business_id)
        ->where('name', $accountName)
        ->first();
    return $account ? $account->id : null;
}

/**
 * Calculate COGS for invoice lines
 */
private function calculateCOGS($invoice)
{
    $totalCOGS = 0;
    foreach ($invoice->lines as $line) {
        // Get last purchase price for the product
        $product = \Modules\Distribution\Entities\Core\Product::find($line->product_id);
        if ($product) {
            // Get last purchase price from purchase lines
            $lastPurchasePrice = \Modules\Distribution\Entities\Core\PurchaseLine::where('product_id', $line->product_id)
                ->orderBy('id', 'desc')
                ->value('purchase_price');
            
            if ($lastPurchasePrice) {
                $totalCOGS += $line->qty * $lastPurchasePrice;
            }
        }
    }
    return $totalCOGS;
}

/**
 * Create account transaction
 */
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

public function updateShippingStatus(Request $request, $id)
{
    $business_id = session()->get('user.business_id');
    $request->validate([
        'shipping_status' => 'required|in:ordered,packed,shipped,delivered,cancelled',
    ]);

    $invoice = DistributionInvoice::where('business_id', $business_id)->findOrFail($id);
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
    $invoice = DistributionInvoice::where('business_id', $business_id)
        ->with(['lines', 'cheques'])
        ->findOrFail($id);

    DB::beginTransaction();
    try {
        $newInvoice = $invoice->replicate();
        $newInvoice->invoice_no = $this->generateInvoiceNumber();
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

        DB::commit();

        return redirect()->route('distribution.invoices.edit', $newInvoice->id)
            ->with('status', 'Invoice duplicated successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors($e->getMessage());
    }
}

public function destroy($id)
{
    $business_id = session()->get('user.business_id');
    $invoice = DistributionInvoice::where('business_id', $business_id)->findOrFail($id);

    DB::beginTransaction();
    try {
        $invoice->lines()->delete();
        $invoice->cheques()->delete();
        \Modules\Distribution\Entities\Core\AccountTransaction::where('transaction_id', $invoice->id)
            ->whereIn('sub_type', ['dis_invoice', 'dis_invoice_payment', 'dis_invoice_credit'])
            ->delete();
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
            'invoice_no' => 'Invoice No',
            'date' => 'Date & Time',
            'delivery_date' => 'Delivery Date',
            'customer_name' => 'Customer Name',
            'customer_contact' => 'Customer Contact Number',
            'customer_address' => 'Location',
            'grand_total' => 'Total Amount',
            'payment_total' => 'Total Paid',
            'payment_cash' => 'Cash Payment',
            'payment_card' => 'Card Payment',
            'payment_credit' => 'Credit Payment',
            'payment_cheque' => 'Cheque Payment',
            'shipping_status' => 'Shipping Status',
            'status' => 'Invoice Status',
            'invoice_note' => 'Invoice Note',
            'shipping_note' => 'Shipping Note',
            'shipping_details' => 'Shipping Details',
        ];
    }

    private function formatDistributionActivityValue(string $key, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if (in_array($key, ['grand_total', 'payment_total', 'payment_cash', 'payment_card', 'payment_credit', 'payment_cheque'], true)) {
            return number_format((float) $value, 2, '.', '');
        }

        if ($key === 'shipping_status' || $key === 'status') {
            return ucfirst(str_replace('_', ' ', (string) $value));
        }

        return (string) $value;
    }

    private function fallbackInvoiceActivityPayloads(DistributionInvoice $invoice): array
    {
        return [
            'created' => 'Created by '
                . (optional($invoice->addedUser)->username ?? optional($invoice->addedUser)->first_name ?? 'System')
                . ' on ' . optional($invoice->created_at)->format('Y-m-d H:i:s'),
            'changed' => 'Updated by '
                . (optional($invoice->updatedUser)->username ?? optional($invoice->updatedUser)->first_name ?? 'System')
                . ' on ' . optional($invoice->updated_at)->format('Y-m-d H:i:s'),
            'deleted' => '-',
        ];
    }
}
