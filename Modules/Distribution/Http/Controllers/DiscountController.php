<?php
namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Entities\Core\Business;
use Modules\Distribution\Entities\Core\Category;
use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\Product;
use Modules\Distribution\Entities\Core\Unit;
use Modules\Distribution\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\Distribution_discount;
use Modules\Distribution\Entities\Distribution_discount_product;

class DiscountController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ModuleUtil $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /** Show Discount Index Page */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            // Start from discount_products with joins for sorting
            $discountProducts = Distribution_discount_product::select(
                'distribution_discount_products.*',
                'distribution_discounts.date_time as discount_date_time',
                'categories.name as category_name',
                'subcategories.name as sub_category_name',
                'products.name as product_name',
                'units.actual_name as unit_name'
            )
                ->join('distribution_discounts', 'distribution_discount_products.discount_id', '=', 'distribution_discounts.id')
                ->leftJoin('categories', 'distribution_discounts.category_id', '=', 'categories.id')
                ->leftJoin('categories as subcategories', 'distribution_discounts.sub_category_id', '=', 'subcategories.id')
                ->leftJoin('products', 'distribution_discount_products.product_id', '=', 'products.id')
                ->leftJoin('units', 'distribution_discount_products.unit_id', '=', 'units.id')
                ->where('distribution_discounts.business_id', $business_id);

            // Handle sorting
            if ($request->has('order') && count($request->order)) {
                foreach ($request->order as $order) {
                    $columnIndex = $order['column'];
                    $columnName  = $request->columns[$columnIndex]['name'];
                    $dir         = $order['dir'];

                    switch ($columnName) {
                        case 'date_time':
                            $discountProducts->orderBy('distribution_discounts.date_time', $dir);
                            break;
                        case 'discount.category.name':
                        case 'category_id':
                            $discountProducts->orderBy('categories.name', $dir);
                            break;
                        case 'discount.subcategory.name':
                        case 'sub_category_id':
                            $discountProducts->orderBy('subcategories.name', $dir);
                            break;
                        case 'product.name':
                        case 'product_id':
                            $discountProducts->orderBy('products.name', $dir);
                            break;
                        case 'unit.actual_name':
                        case 'unit_id':
                            $discountProducts->orderBy('units.actual_name', $dir);
                            break;
                        default:
                            // For columns in distribution_discount_products table
                            if (in_array($columnName, ['qty', 'discount_type', 'max_discount'])) {
                                $discountProducts->orderBy('distribution_discount_products.' . $columnName, $dir);
                            } else {
                                $discountProducts->orderBy('distribution_discounts.date_time', 'desc');
                            }
                            break;
                    }
                }
            } else {
                // Default sorting by date_time
                $discountProducts->orderBy('distribution_discounts.date_time', 'desc');
            }

            // Handle search
            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $discountProducts->where(function ($query) use ($search) {
                    $query->where('distribution_discounts.date_time', 'like', "%{$search}%")
                        ->orWhere('categories.name', 'like', "%{$search}%")
                        ->orWhere('subcategories.name', 'like', "%{$search}%")
                        ->orWhere('products.name', 'like', "%{$search}%")
                        ->orWhere('units.actual_name', 'like', "%{$search}%")
                        ->orWhere('distribution_discount_products.qty', 'like', "%{$search}%")
                        ->orWhere('distribution_discount_products.discount_type', 'like', "%{$search}%")
                        ->orWhere('distribution_discount_products.max_discount', 'like', "%{$search}%");
                });
            }

            // Get total count BEFORE pagination
            $totalQuery = clone $discountProducts;
            $total      = $totalQuery->count();

            // Apply pagination
            if ($request->has('length') && $request->length != -1) {
                $discountProducts->skip($request->start)->take($request->length);
            }

            $results = $discountProducts->get();

            // Get business settings for formatting
            $business = Business::find($business_id);
            $currency_precision = (!empty($business) && !empty($business->currency_precision)) ? $business->currency_precision : 2;
            $quantity_precision = (!empty($business) && !empty($business->quantity_precision)) ? $business->quantity_precision : 2;
            
            // Get currency settings safely
            $currency = session('currency', []);
            $decimal_separator = (!empty($business) && !empty($business->decimal_separator)) ? $business->decimal_separator : ($currency['decimal_separator'] ?? '.');
            $thousand_separator = (!empty($business) && !empty($business->thousand_separator)) ? $business->thousand_separator : ($currency['thousand_separator'] ?? ',');

            $data = [];
            foreach ($results as $row) {
                // Format qty with quantity precision
                $formatted_qty = '-';
                if ($row->qty !== null && $row->qty !== '') {
                    $formatted_qty = number_format((float)$row->qty, $quantity_precision, $decimal_separator, $thousand_separator);
                }

                // Format max_discount with currency precision
                $formatted_max_discount = '-';
                if ($row->max_discount !== null && $row->max_discount !== '') {
                    $formatted_max_discount = number_format((float)$row->max_discount, $currency_precision, $decimal_separator, $thousand_separator);
                }

                $data[] = [
                    'date_time'         => $row->discount_date_time
                        ? \Carbon\Carbon::parse($row->discount_date_time)->format('Y-m-d H:i')
                        : '-',
                    'category_name'     => $row->category_name ?? '-',
                    'sub_category_name' => $row->sub_category_name ?? '-',
                    'product_name'      => $row->product_name ?? '-',
                    'unit_name'         => $row->unit_name ?? '-',
                    'qty'               => $formatted_qty,
                    'discount_type'     => ucfirst($row->discount_type ?? '-'),
                    'max_discount'      => $formatted_max_discount,
                ];
            }

            Log::info('Data Table Response:', ['count' => count($data), 'data_sample' => $data[0] ?? []]);

            try {
                return response()->json([
                    'draw'            => (int) $request->draw,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                    'data'            => $data,
                ]);
            } catch (\Exception $e) {
                Log::error('Discount table error: ' . $e->getMessage());
                return response()->json([
                    'draw'            => (int) $request->draw,
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => 'An error occurred while loading data.',
                ], 500);
            }
        }

        return view('distribution::settings.discounts.index');
    }

    /** Show Create Discount Form */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        Log::info('Create Discount - Business ID: ' . $business_id);

        // Get main categories (where parent_id is null)
        $main_categories = Category::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->pluck('name', 'id');

        Log::info('Main Categories:', $main_categories->toArray());

        // Units dropdown - use pluck to get id and name
        $units = Unit::where('business_id', $business_id)
            ->pluck('actual_name', 'id');

        // Get business settings for precision
        $business = Business::find($business_id);
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;

        // Check if auto_date_time is enabled
        $auto_date_time = false;
        if (method_exists($this, 'moduleUtil')) {
            $auto_date_time = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'distribution_auto_date_time');
        }

        return view('distribution::settings.discounts.create')
            ->with(compact('main_categories', 'units', 'currency_precision', 'quantity_precision', 'auto_date_time'));
    }

    /** Store Discount */
    public function store(Request $request)
    {
        Log::info('store request: ', $request->all());

        $business_id = $request->session()->get('user.business_id');

        // Save parent discount (basic info only)
        // Convert empty strings to null for category_id and sub_category_id
        $discount = Distribution_discount::create([
            'business_id'     => $business_id,
            'date_time'       => $request->date_time,
            'category_id'     => !empty($request->category_id) ? $request->category_id : null,
            'sub_category_id' => !empty($request->sub_category_id) ? $request->sub_category_id : null,
        ]);

        // Save products
        if ($request->product_ids) {
            foreach ($request->product_ids as $index => $product_id) {
                Distribution_discount_product::create([
                    'discount_id'   => $discount->id,
                    'product_id'    => $product_id,
                    'unit_id'       => $request->unit_ids[$index] ?? null,
                    'qty'           => $request->qty[$index] ?? null,
                    'discount_type' => $request->discount_type[$index] ?? null,
                    'max_discount'  => $request->max_discount[$index] ?? null,
                ]);
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'msg'     => __("Discount added successfully"),
            ]);
        }

        return redirect()->back()->with('status', [
            'success' => true,
            'msg'     => __("Discount added successfully"),
        ]);
    }

    /** Get Sub Categories (Ajax) */
    public function getSubCategories(Request $request)
    {
        Log::info('Get SubCategories Request:', $request->all());
        $business_id = request()->session()->get('user.business_id');
        $category_id = $request->category_id;

        // Validate business_id exists
        if (!$business_id) {
            Log::error('No business_id found in session');
            return response()->json([]);
        }

        try {
            $query = Category::where('business_id', $business_id)
                ->where('parent_id', '!=', 0); // Only subcategories

            // If specific category selected, filter by parent category
            if ($category_id && $category_id !== '' && $category_id !== 'all') {
                $query->where('parent_id', $category_id);
            }
            // If "all" or empty, return ALL subcategories for this business

            $subcategories = $query->orderBy('name')->get(['id', 'name']);

            Log::info('SubCategories found:', ['count' => $subcategories->count()]);
            
            return response()->json($subcategories);
            
        } catch (\Exception $e) {
            Log::error('Error loading subcategories: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /** Get Products (Ajax) */
    public function getProducts(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $query = Product::where('business_id', $business_id)
            ->where(function($q) {
                $q->where('is_inactive', 0)
                  ->orWhereNull('is_inactive');
            })
            ->where(function($q) {
                $q->where('not_for_selling', 0)
                  ->orWhereNull('not_for_selling');
            })
            ->where('type', '!=', 'modifier');

        $sub_category_id = $request->input('sub_category_id');
        $category_id = $request->input('category_id');

        // If sub_category_id is provided, filter by it (more specific)
        if (!empty($sub_category_id) && $sub_category_id !== '0' && $sub_category_id !== 'all') {
            $query->where('sub_category_id', $sub_category_id);
        } 
        // Otherwise, if category_id is provided, filter by it
        elseif (!empty($category_id) && $category_id !== '0' && $category_id !== 'all') {
            $query->where('category_id', $category_id);
        }

        return $query->orderBy('id', 'desc')->get(['id', 'name', 'unit_id'])
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'unit_id' => $p->unit_id]);
    }

    public function show($id)
    {
        return redirect()->back();

    }

    /** Get Units for a specific product (Ajax) */
    public function getProductUnits(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $product_id = $request->input('product_id');

        if (empty($product_id)) {
            return response()->json([]);
        }

        $product = Product::where('business_id', $business_id)
            ->find($product_id);

        if (empty($product)) {
            return response()->json([]);
        }

        // Get sub units using the Util helper
        $util = new \Modules\Distribution\Utils\Util();
        $sub_units = $util->getSubUnits($business_id, $product->unit_id, true, $product_id);

        // Format for dropdown
        $units = [];
        foreach ($sub_units as $unit_id => $unit_data) {
            $units[] = [
                'id' => $unit_id,
                'name' => $unit_data['name'],
                'multiplier' => $unit_data['multiplier'],
                'allow_decimal' => $unit_data['allow_decimal']
            ];
        }

        return response()->json($units);
    }
}
