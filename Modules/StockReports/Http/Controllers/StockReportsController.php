<?php

namespace Modules\StockReports\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Brands;
use App\Contact;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Exports\ProductsExport;
use App\Media;
use App\Product;
use App\ProductVariation;
use App\PurchaseLine;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Unit;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Transaction;
use Yajra\DataTables\Facades\DataTables;
use App\Variation;
use App\VariationGroupPrice;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use App\VariationTemplate;
use App\Warranty;
use App\Account;
use App\Store;
use App\Variation_store_detail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;


use App\Utils\ContactUtil;
use App\Utils\BusinessUtil;

class StockReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
       protected $productUtil;

   

    protected $moduleUtil;

    protected $contactUtil;
public function __construct(ProductUtil $productUtil,  BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ContactUtil $contactUtil)
    {
        $this->productUtil = $productUtil;



        $this->businessUtil = $businessUtil;
           $this->moduleUtil = $moduleUtil;

        $this->contactUtil = $contactUtil;

    }
 public function index()
{
    
    $business_id = request()->session()->get('user.business_id');

    // S740: The report View action is served through this already-authorized
    // Stock Transaction Report endpoint. This deliberately avoids forwarding
    // the user to core Sales/Purchase/Adjustment show routes, whose separate
    // permissions can return HTTP 403 even though the user is allowed to view
    // this report. Keeping the request on the current report route also keeps
    // the active tenant/session context intact.
    if (request()->ajax() && request()->boolean('stock_report_view')) {
        $transaction_id = (int) request()->input('transaction_id');

        return $this->renderTransactionModal($transaction_id, $business_id);
    }

    $categories = Category::forDropdown($business_id, 'product');
    $brands = Brands::forDropdown($business_id);
    $units = Unit::forDropdown($business_id);
    $stores= Store::forDropdown($business_id);
    $tax_dropdown = TaxRate::forBusinessDropdown($business_id, false);
    $taxes = $tax_dropdown['tax_rates'];
    $business_locations = BusinessLocation::forDropdown($business_id);

    if (request()->ajax()) {

            // Get all filter parameters
        $location_id = request()->input('location_id');
        $category_id = request()->input('category_id');
        $sub_category_id = request()->input('sub_category_id');
        $brand_id = request()->input('brand_id');
        $product_id = request()->input('product_id');
        $unit_id = request()->input('unit_id');
        $store_id = request()->input('store_id');
        $product_type = request()->input('product_type');
        $sku = request()->input('sku');

        // Parse date range using the business date format from session
        $date_range = request()->input('date_range');
        $start_date = null;
        $end_date = null;
        $date_format = session('business.date_format', 'm/d/Y');

        if (!empty($date_range)) {
            $dates = explode(' - ', $date_range);
            if (count($dates) == 2) {
                try {
                    $start_date = Carbon::createFromFormat($date_format, trim($dates[0]))->startOfDay()->format('Y-m-d H:i:s');
                    $end_date = Carbon::createFromFormat($date_format, trim($dates[1]))->endOfDay()->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Fallback: try common formats
                    foreach (['m/d/Y', 'd/m/Y', 'd-m-Y', 'm-d-Y', 'Y-m-d'] as $fmt) {
                        try {
                            $start_date = Carbon::createFromFormat($fmt, trim($dates[0]))->startOfDay()->format('Y-m-d H:i:s');
                            $end_date = Carbon::createFromFormat($fmt, trim($dates[1]))->endOfDay()->format('Y-m-d H:i:s');
                            break;
                        } catch (\Exception $e2) {
                            continue;
                        }
                    }
                }
            }
        }
        // Start query for transactions with all necessary joins.
        // Sell return quantities live on the parent sale lines, not on the
        // sell_return transaction itself, so we join the parent sale lines too.
        $baseQuery = Transaction::selectRaw(
            'transactions.id,
            transactions.transaction_date,
            transactions.ref_no,
            transactions.invoice_no,
            transactions.payment_status,
            transactions.status,
            transactions.type,
            bl.name AS business_location,
            COALESCE(tsl.product_id, parent_tsl.product_id, tpl.product_id, sal.product_id) AS product_id,
            COALESCE(p.name, parent_sale_product.name, purchase_product.name, sa_product.name) AS product_name,
            COALESCE(p.sku, parent_sale_product.sku, purchase_product.sku, sa_product.sku) AS product_sku,
            COALESCE(p.category_id, parent_sale_product.category_id, purchase_product.category_id, sa_product.category_id) AS category_id,
            COALESCE(p.sub_category_id, parent_sale_product.sub_category_id, purchase_product.sub_category_id, sa_product.sub_category_id) AS sub_category_id,
            COALESCE(cat.name, parent_sale_cat.name, purchase_cat.name, sa_cat.name, "Uncategorized") AS category_name,
            COALESCE(s.name, parent_store.name) as store_name,
            from_s.name AS from_store,
            to_s.name AS to_store,
            tsl.quantity AS qty_issue,
            parent_tsl.quantity AS parent_qty_issue,
            COALESCE(parent_tsl.quantity_returned, tsl.quantity_returned, 0) AS sales_qty_return,
            tpl.quantity AS qty_recieve,
            tpl.quantity_returned AS quantity_returned,
            tpl.bonus_qty AS bonus_qty,
            u.username AS user,
            COALESCE(tsl.variation_id, parent_tsl.variation_id, tpl.variation_id, sal.variation_id) AS variation_id,
            sal.quantity AS sa_qty,
            sal.stock_adjustment_type AS sa_line_type,
            sal.id AS sal_id,
            contacts.name as contact_name'
        )
        ->leftjoin('transaction_sell_lines AS tsl', 'transactions.id', '=', 'tsl.transaction_id')
        ->leftjoin('transactions AS parent_sell', 'transactions.return_parent_id', '=', 'parent_sell.id')
        ->leftjoin('transaction_sell_lines AS parent_tsl', 'parent_sell.id', '=', 'parent_tsl.transaction_id')
        ->leftjoin('purchase_lines AS tpl', 'tpl.transaction_id', '=', 'transactions.id')
        ->leftjoin('stock_adjustment_lines AS sal', 'sal.transaction_id', '=', 'transactions.id')
        ->leftjoin('business_locations AS bl', 'transactions.location_id', '=', 'bl.id')
        ->leftjoin('products AS p', 'tsl.product_id', '=', 'p.id')
        ->leftjoin('products AS parent_sale_product', 'parent_tsl.product_id', '=', 'parent_sale_product.id')
        ->leftjoin('products AS purchase_product', 'tpl.product_id', '=', 'purchase_product.id')
        ->leftjoin('products AS sa_product', 'sal.product_id', '=', 'sa_product.id')
        ->leftjoin('categories AS cat', 'p.category_id', '=', 'cat.id')
        ->leftjoin('categories AS parent_sale_cat', 'parent_sale_product.category_id', '=', 'parent_sale_cat.id')
        ->leftjoin('categories AS purchase_cat', 'purchase_product.category_id', '=', 'purchase_cat.id')
        ->leftjoin('categories AS sa_cat', 'sa_product.category_id', '=', 'sa_cat.id')
        ->leftjoin('stores AS s', 'transactions.store_id', '=', 's.id')
        ->leftjoin('stores AS parent_store', 'parent_sell.store_id', '=', 'parent_store.id')
        ->leftjoin('stores AS from_s', 'transactions.From_Account', '=', 'from_s.id')
        ->leftjoin('stores AS to_s', 'transactions.To_Account', '=', 'to_s.id')
        ->leftjoin('users AS u', 'transactions.created_by', '=', 'u.id')
        ->leftjoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
        ->where('transactions.business_id', $business_id)
        ->whereIn('transactions.type', [
            'sell',
            'purchase',
            'Stock_transfer',
            'purchase_transfer',
            'sell_transfer',
            'sell_return',
            'purchase_return',
            'stock_adjustment',
            'production_purchase',
            'production_sell',
            'opening_stock'
        ]);
        
      // Apply location filter if selected
        if ($location_id && $location_id != 'none') {
            $baseQuery->where('transactions.location_id', $location_id);
        }
               // Apply product type filter
        if (!empty($product_type)) {
            $baseQuery->where(function($q) use ($product_type) {
                $q->where('p.type', $product_type)
                  ->orWhere('parent_sale_product.type', $product_type)
                  ->orWhere('purchase_product.type', $product_type)
                  ->orWhere('sa_product.type', $product_type);
            });
        }
         // Apply category filter
        if (!empty($category_id)) {
            $baseQuery->where(function($q) use ($category_id) {
                $q->where('p.category_id', $category_id)
                  ->orWhere('parent_sale_product.category_id', $category_id)
                  ->orWhere('purchase_product.category_id', $category_id)
                  ->orWhere('sa_product.category_id', $category_id);
            });
        }

        // Apply sub-category filter
        if (!empty($sub_category_id)) {
            $baseQuery->where(function($q) use ($sub_category_id) {
                $q->where('p.sub_category_id', $sub_category_id)
                  ->orWhere('parent_sale_product.sub_category_id', $sub_category_id)
                  ->orWhere('purchase_product.sub_category_id', $sub_category_id)
                  ->orWhere('sa_product.sub_category_id', $sub_category_id);
            });
        }
        if (!empty($unit_id)) {
            $baseQuery->where(function($q) use ($unit_id) {
                $q->where('p.unit_id', $unit_id)
                  ->orWhere('parent_sale_product.unit_id', $unit_id)
                  ->orWhere('purchase_product.unit_id', $unit_id)
                  ->orWhere('sa_product.unit_id', $unit_id);
            });
        }
        if (!empty($store_id)) {
            $baseQuery->where(function($q) use ($store_id) {
                $q->where('s.id', $store_id)
                  ->orWhere('parent_store.id', $store_id);
            });
        }

        // Apply brand filter
        if (!empty($brand_id)) {
            $baseQuery->where(function($q) use ($brand_id) {
                $q->where('p.brand_id', $brand_id)
                  ->orWhere('parent_sale_product.brand_id', $brand_id)
                  ->orWhere('purchase_product.brand_id', $brand_id)
                  ->orWhere('sa_product.brand_id', $brand_id);
            });
        }

        // Apply product filter
        if (!empty($product_id)) {
            $baseQuery->where(function($q) use ($product_id) {
                $q->where('tsl.product_id', $product_id)
                  ->orWhere('parent_tsl.product_id', $product_id)
                  ->orWhere('tpl.product_id', $product_id)
                  ->orWhere('sal.product_id', $product_id);
            });
        }

        // Apply SKU filter
        if (!empty($sku)) {
            $baseQuery->where(function($q) use ($sku) {
                $q->where('p.sku', 'like', '%' . $sku . '%')
                  ->orWhere('parent_sale_product.sku', 'like', '%' . $sku . '%')
                  ->orWhere('purchase_product.sku', 'like', '%' . $sku . '%')
                  ->orWhere('sa_product.sku', 'like', '%' . $sku . '%');
            });
        }

        $opening_balances = [];
        if (!empty($start_date)) {
            $openingTransactions = (clone $baseQuery)
                ->where('transactions.transaction_date', '<', $start_date)
                ->orderBy('transactions.transaction_date', 'asc')
                ->orderBy('transactions.id', 'asc')
                ->get();

            foreach ($openingTransactions as $transaction) {
                $metrics = $this->getStockReportTransactionMetrics($transaction);
                if ($transaction->type == 'sell_return' && (float) $metrics['transaction_qty'] === 0.0) {
                    continue;
                }

                if (empty($metrics['product_id']) || empty($metrics['variation_id'])) {
                    continue;
                }

                $product_variation_key = $metrics['product_id'] . '_' . $metrics['variation_id'];
                if (!isset($opening_balances[$product_variation_key])) {
                    $opening_balances[$product_variation_key] = 0;
                }

                $opening_balances[$product_variation_key] += $metrics['transaction_effect'];
            }
        }

        $query = clone $baseQuery;
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('transactions.transaction_date', [$start_date, $end_date]);
        }

        // Get transactions sorted by date for running balance calculation
        $transactions = $query->orderByRaw('COALESCE(cat.name, parent_sale_cat.name, purchase_cat.name, sa_cat.name, "Uncategorized") asc')
            ->orderByRaw('COALESCE(p.name, parent_sale_product.name, purchase_product.name, sa_product.name) asc')
            ->orderBy('transactions.transaction_date', 'asc')
            ->orderBy('transactions.id', 'asc')
            ->get();
        
        $result = [];
        $product_variation_balances = [];
        
        foreach ($transactions as $transaction) {
            $metrics = $this->getStockReportTransactionMetrics($transaction);
            if ($transaction->type == 'sell_return' && (float) $metrics['transaction_qty'] === 0.0) {
                continue;
            }

            $product_id = $metrics['product_id'];
            $product_name = $transaction->product_name;
            $sku = $transaction->product_sku;
            $variation_id = $metrics['variation_id'];
            $transaction_qty = $metrics['transaction_qty'];
            $transaction_effect = $metrics['transaction_effect'];
            $bonus_qty = $metrics['bonus_qty'];

            if (!$product_id || !$variation_id) {
                continue;
            }

            $product_variation_key = $product_id . '_' . $variation_id;
            if (!isset($product_variation_balances[$product_variation_key])) {
                $product_variation_balances[$product_variation_key] = $opening_balances[$product_variation_key] ?? 0;
            }
            
            // Calculate running balance for this product-variation
            $balance_before = $product_variation_balances[$product_variation_key];
            $product_variation_balances[$product_variation_key] += $transaction_effect;
            $balance_after = $product_variation_balances[$product_variation_key];

            // Show the running quantity before each transaction as the starting qty.
            // Keep opening stock rows readable by showing the opening stock quantity
            // when there is no prior balance for that product/variation.
            $display_opening_qty = $balance_before;
            if ((float) $display_opening_qty === 0.0 && $transaction->type == 'opening_stock') {
                $display_opening_qty = $transaction_qty;
            }

            // Get store transfer information
            $from_store = $transaction->from_store ?? 'N/A';
            $to_store = $transaction->to_store ?? 'N/A';

            // Prepare store display
            $store_display = $transaction->store_name ?? 'N/A';
            if ($transaction->type == 'Stock_transfer') {
                $store_display = "From: $from_store → To: $to_store";
            }
            
            $result[] = [
                'date_time' => $transaction->transaction_date,
                'product' => $product_name,
                'sku' => $sku,
                'description' => $transaction->ref_no,
                'transaction_no' => $transaction->invoice_no ?? $transaction->ref_no,
                'party' => $transaction->contact_name ?? 'N/A',
                'transaction_type' => $transaction->type,
                'opening_qty' =>  $display_opening_qty,
                'purchase_qty' => in_array($transaction->type, ['purchase', 'purchase_transfer', 'production_purchase']) ? $transaction_qty : 0,
                'bonus_qty' => in_array($transaction->type, ['purchase', 'purchase_transfer', 'production_purchase']) ? $bonus_qty : 0,
                'purchase_return_qty' => $transaction->type == 'purchase_return' ? $transaction_qty : 0,
                'stock_adjustment_qty' => $transaction->type == 'stock_adjustment' ? $transaction_qty : 0,
                'sold_qty' => in_array($transaction->type, ['sell', 'sell_transfer', 'production_sell']) ? $transaction_qty : 0,
                'sales_return_qty' => $transaction->type == 'sell_return' ? $transaction_qty : 0,
                'transfer_qty' => $transaction->type == 'Stock_transfer' ? abs($transaction_qty) : 0,
                'transfer_direction' => $transaction->type == 'Stock_transfer' ? ($transaction_effect > 0 ? 'IN' : 'OUT') : '',
                'balance_before' => $balance_before,
                'transaction_qty' => $transaction_effect,
                'balance_after' => $balance_after,
                'balance_qty' => $balance_after,
                'location' => $transaction->business_location,
                'store' => $store_display,
                'user' => $transaction->user,
                'reference_no' => $transaction->ref_no,
                'payment_status' => $transaction->payment_status,
                'status' => $transaction->status,
                'transaction_id' => $transaction->id,
                'from_store' => $transaction->from_store,
                'to_store' => $to_store,
                'category_name' => $transaction->category_name ?? 'Uncategorized',
                'section_name' => $transaction->category_name ?? 'Uncategorized',
            ];
        }
         
      return DataTables::of($result)
   ->addColumn('action', function ($row) {
    if (empty($row['transaction_id'])) {
        return '';
    }

    // S740: Always open the module-owned transaction view. Do not send the
    // report user to core transaction show routes because those routes have
    // their own permissions and were the source of the reported HTTP 403.
    return '<button type="button"
            class="btn btn-primary btn-xs stockreport-view-transaction"
            data-transaction-id="' . (int) $row['transaction_id'] . '">
            <i class="fa fa-eye"></i> ' . __("messages.view") . '
        </button>';
})
            
              ->addColumn(
                    'sku',
                    '{{$sku}}'
                )
                
                ->addColumn(
                    'product',
                    '{{$product}}'
                )
                ->addColumn(
                    'from_store',
                    '{{$from_store}}'
                )
                 ->addColumn(
                    'date_time',
                    '{{$date_time}}'
                )
                 ->addColumn(
                    'description',
                    '{{$description}}'
                )
            ->editColumn('transaction_type', function($row) {
                $types = [
                    'sell' => __('sale.sale'),
                    'purchase' => __('purchase.purchase'),
                    'Stock_transfer' => __('lang_v1.stock_transfer'),
                    'sell_return' => __('lang_v1.sell_return'),
                    'purchase_return' => __('lang_v1.purchase_return'),
                    'stock_adjustment' => __('stock_adjustment.stock_adjustment'),
                    'purchase_transfer' => __('lang_v1.purchase_transfer'),
                    'sell_transfer' => __('lang_v1.sell_transfer'),
                    'production_purchase' => __('manufacturing::lang.production'),
                    'production_sell' => __('manufacturing::lang.production_sell'),
                ];
                
                $type = $row['transaction_type'];
                $display = $types[$type] ?? $type;
                
                // Add transfer direction indicator
                if ($type == 'Stock_transfer' && isset($row['transfer_direction'])) {
                    $direction = $row['transfer_direction'] == 'IN' ? ' (Incoming)' : ' (Outgoing)';
                    $display .= $direction;
                }
                
                return $display;
            })
            ->addColumn('balance_before', function($row) {
                return $this->formatQuantity($row['balance_before']);
            })
            ->addColumn('transaction_effect', function($row) {
                $effect = $row['transaction_qty'];
                $class = $effect >= 0 ? 'text-success' : 'text-danger';
                $sign = $effect >= 0 ? '+' : '';
                return '<span class="' . $class . '">' . $sign . $this->formatQuantity($effect) . '</span>';
            })
            ->editColumn('stock_adjustment_qty', function($row) {
                $qty = $row['stock_adjustment_qty'];
                if ($qty < 0) {
                    return '<span class="text-danger">' . $this->formatQuantity($qty) . '</span>';
                } elseif ($qty > 0) {
                    return '<span class="text-success">+' . $this->formatQuantity($qty) . '</span>';
                }
                return $this->formatQuantity($qty);
            })
            ->editColumn('purchase_qty', function($row) {
                $qty = $row['purchase_qty'];
                return $qty > 0 ? '<span class="text-success">+' . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('bonus_qty', function($row) {
                $qty = $row['bonus_qty'];
                return $qty > 0 ? '<span class="text-success">+' . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('description', function ($row) {
                $parts = [];

                if (!empty($row['transaction_no'])) {
                    $parts[] = $row['transaction_no'];
                } elseif (!empty($row['reference_no'])) {
                    $parts[] = $row['reference_no'];
                }

                if (!empty($row['party']) && $row['party'] != 'N/A') {
                    $parts[] = $row['party'];
                }

                if (!empty($parts)) {
                    return implode(' | ', $parts);
                }

                return 'Transaction #' . ($row['transaction_id'] ?? '');
            })
        
            ->editColumn('purchase_return_qty', function($row) {
                $qty = $row['purchase_return_qty'];
                return $qty > 0 ? '<span class="text-danger">-' . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('sold_qty', function($row) {
                $qty = $row['sold_qty'];
                return $qty > 0 ? '<span class="text-danger">-' . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('sales_return_qty', function($row) {
                $qty = $row['sales_return_qty'];
                return $qty > 0 ? '<span class="text-success">+' . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('transfer_qty', function($row) {
                $qty = $row['transfer_qty'];
                $direction = $row['transfer_direction'] ?? '';
                $class = $direction == 'IN' ? 'text-success' : 'text-danger';
                $sign = $direction == 'IN' ? '+' : '-';
                return $qty > 0 ? '<span class="' . $class . '">' . $sign . $this->formatQuantity($qty) . '</span>' : $this->formatQuantity($qty);
            })
            ->editColumn('balance_qty', function($row) {
                $qty = $row['balance_qty'];
                $class = $qty < 0 ? 'text-danger' : ($qty == 0 ? 'text-warning' : 'text-success');
                return '<span class="' . $class . ' font-weight-bold">' . $this->formatQuantity($qty) . '</span>';
            })
            ->editColumn('store', function($row) {
                return $row['store'];
            })
            ->editColumn('user', function($row) {
                return $row['user'];
            })
            ->rawColumns([
                'action', 
                'stock_adjustment_qty', 
                'balance_qty', 
                'balance_before',
                'transaction_effect',
                'purchase_qty',
                'bonus_qty',
                'purchase_return_qty',
                'sold_qty',
                'sales_return_qty',
                'transfer_qty',
                'store'
            ])
            ->make(true);
            
    }
    
    $products = Product::where('business_id', $business_id)->pluck('name', 'id');
    $sub_categories = Category::subCategoryforDropdown($business_id, true);
    
    return view('stockreports::index')->with(compact(
        'products',
        'sub_categories',
        'categories',
        'brands',
        'units',
        'taxes',
        'business_locations',
        'stores'
    ));
}

/**
 * Get the URL to view a transaction based on its type
 */
private function getTransactionUrl($row)
{
    $type = $row['transaction_type'] ?? '';
    $id = $row['transaction_id'] ?? 0;
    
    if (!$id) {
        return '#';
    }
    $routes = [
        'purchase' => action('\App\Http\Controllers\PurchaseController@show', $id),
        'sell' => action('\App\Http\Controllers\SellController@show', $id),
        'sell_return' => action('\App\Http\Controllers\SellReturnController@show', $id),
        'purchase_return' => action('\App\Http\Controllers\PurchaseReturnController@show', $id),
        'stock_adjustment' => action('\App\Http\Controllers\StockAdjustmentController@show', $id),
        'opening_stock' => action('\App\Http\Controllers\PurchaseController@show', $id),
    ];

    if (empty($routes[$type])) {
        return '#';
    }

    return $routes[$type] . (str_contains($routes[$type], '?') ? '&' : '?') . 'stock_report_footer=1';
}

/**
 * Helper function to get variation description
 */
private function getVariationDescription($variation_id)
{
    if (!$variation_id) {
        return '';
    }
    
    $variation = DB::table('variations')
        ->where('id', $variation_id)
        ->select('name')
        ->first();
    
    return $variation->name ?? '';
}

private function getStockReportTransactionMetrics($transaction)
{
    $product_id = $transaction->product_id;
    $variation_id = $transaction->variation_id;
    $transaction_qty = 0;
    $transaction_effect = 0;
    $bonus_qty = 0;

    switch ($transaction->type) {
        case 'purchase_transfer':
        case 'production_purchase':
        case 'purchase':
            $transaction_qty = (float) ($transaction->qty_recieve ?? 0);
            $bonus_qty = (float) ($transaction->bonus_qty ?? 0);
            $transaction_effect = $transaction_qty + $bonus_qty;
            break;

        case 'sell':
            $transaction_qty = (float) ($transaction->qty_issue ?? 0);
            $transaction_effect = -$transaction_qty;
            break;

        case 'opening_stock':
            $transaction_qty = (float) ($transaction->qty_recieve ?? 0);
            $transaction_effect = $transaction_qty;
            break;

        case 'sell_transfer':
        case 'production_sell':
            $transaction_qty = (float) ($transaction->qty_issue ?? 0);
            $transaction_effect = $transaction_qty;
            break;

        case 'purchase_return':
            $transaction_qty = abs((float) ($transaction->quantity_returned ?? 0));
            $transaction_effect = -$transaction_qty;
            break;

        case 'sell_return':
            $transaction_qty = abs((float) ($transaction->sales_qty_return ?? 0));
            $transaction_effect = $transaction_qty;
            break;

        case 'stock_adjustment':
            $sa_qty = (float) ($transaction->sa_qty ?? 0);
            $sa_line_type = $transaction->sa_line_type ?? 'increase';
            $transaction_qty = $sa_line_type == 'decrease' ? -$sa_qty : $sa_qty;
            $transaction_effect = $transaction_qty;
            break;

        case 'Stock_transfer':
            if ((float) ($transaction->qty_issue ?? 0) > 0) {
                $transaction_qty = (float) ($transaction->qty_issue ?? 0);
                $transaction_effect = -$transaction_qty;
            } else {
                $transaction_qty = (float) ($transaction->qty_recieve ?? 0);
                $transaction_effect = $transaction_qty;
            }
            break;
    }

    return [
        'product_id' => $product_id,
        'variation_id' => $variation_id,
        'transaction_qty' => $transaction_qty,
        'transaction_effect' => $transaction_effect,
        'bonus_qty' => $bonus_qty,
    ];
}

/**
 * Helper function to format quantity
 */
private function formatQuantity($quantity)
{
    return number_format($quantity, 2, '.', ',');
}

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('stockreports::create');
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
        return view('stockreports::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('stockreports::edit');
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
 * Show transaction details in a modal
 */
    public function showTransaction($id)
    {
        $business_id = request()->session()->get('user.business_id');

        return $this->renderTransactionModal((int) $id, $business_id);
    }

    /**
     * Render the Stock Transaction Report's own transaction view.
     *
     * This view is intentionally business-scoped and module-owned. It does
     * not depend on the permissions of the core Sales/Purchase/etc. show
     * pages, so a user who can open this report can inspect the row they are
     * already authorized to see in the report.
     */
    private function renderTransactionModal($id, $business_id)
    {
        if (empty($id) || empty($business_id)) {
            return response(
                '<div class="alert alert-danger" style="margin:15px;">Unable to load this transaction.</div>',
                422
            );
        }

        try {
            $transaction = Transaction::with([
                'contact',
                'location',
                'store',
                'sell_lines',
                'sell_lines.product',
                'purchase_lines',
                'purchase_lines.product',
                'stock_adjustment_lines',
                'stock_adjustment_lines.product'
            ])->where('business_id', $business_id)
              ->find($id);

            if (empty($transaction)) {
                return response(
                    '<div class="alert alert-danger" style="margin:15px;">Transaction not found for this business.</div>',
                    404
                );
            }

            return view('stockreports::partials.transaction_modal')
                ->with(compact('transaction'));
        } catch (\Throwable $e) {
            \Log::error('StockReports transaction view failed', [
                'transaction_id' => $id,
                'business_id' => $business_id,
                'message' => $e->getMessage(),
            ]);

            return response(
                '<div class="alert alert-danger" style="margin:15px;">Unable to load the transaction details.</div>',
                500
            );
        }
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
