<?php
namespace Modules\DisStockTransfer\Http\Controllers;

use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\DisStockTransfer\Entities\DistributionStockTransfer;
use Modules\DisStockTransfer\Entities\DistributionStockTransferLine;
use Modules\Distribution\Entities\DistributionNumberingPrefix;
use Modules\Distribution\Entities\DistributionVehicles;
use Modules\Distribution\Entities\Distribution_routes;
use Yajra\DataTables\DataTables;

class DisStockTransferController extends Controller
{
    protected $commonUtil;
    protected $productUtil;

    public function __construct(Util $commonUtil, ProductUtil $productUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
    }

    public function index()
    {
        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $query = DistributionStockTransfer::leftJoin('business_locations as bl', 'bl.id', 'distribution_stock_transfers.location_id')
                ->leftJoin('stores as st', 'st.id', 'distribution_stock_transfers.store_id')
                ->leftJoin('stores as tst', 'tst.id', 'distribution_stock_transfers.to_store_id')
                ->leftJoin('distribution_vehicles as dv', 'dv.id', 'distribution_stock_transfers.vehicle_id')
                ->where('distribution_stock_transfers.business_id', $business_id)
                ->select([
                    'distribution_stock_transfers.*',
                    'bl.name as location_name',
                    'st.name as store_name',
                    'tst.name as to_store_name',
                    'dv.vehicle_no as vehicle_name',
                    DB::raw('(SELECT SUM(subtotal) FROM distribution_stock_transfer_lines WHERE stock_transfer_id = distribution_stock_transfers.id) as total_amount'),
                ]);

            return Datatables::of($query)
                ->editColumn('date', function ($row) {
                    return \Carbon\Carbon::parse($row->date)->format('Y-m-d');
                })
                ->editColumn('total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->addColumn('action', function ($row) {
                    $html = '<button
                                data-href="' . route('disstocktransfer.dis-stock-transfer.show', $row->id) . '"
                                class="btn btn-xs btn-primary view-transfer">
                                <i class="fa fa-eye"></i>
                            </button> ';

                    $html .= '<a href="' . route('disstocktransfer.dis-stock-transfer.edit', $row->id) . '"
                                class="btn btn-xs btn-warning">
                                <i class="fa fa-pencil"></i>
                            </a> ';

                    $html .= '<button data-href="' . route('disstocktransfer.dis-stock-transfer.destroy', $row->id) . '"
                                class="btn btn-xs btn-danger delete_item">
                                <i class="fa fa-trash"></i>
                            </button>';

                    return $html;
                })

                ->rawColumns(['action'])
                ->make(true);
        }

        return view('disstocktransfer::index');
    }

    /**
     * Custom reference generator using distribution_prefix_settings table
     */
    private function generateRefNo($business_id)
    {
        return DB::transaction(function () use ($business_id) {

            $type = 'stock_transfer';

            $prefixRow = DistributionNumberingPrefix::where('business_id', $business_id)
                ->where('numbering_type', $type)
                ->lockForUpdate()
                ->first();

            if (! $prefixRow) {
                $prefixRow = DistributionNumberingPrefix::create([
                    'business_id'    => $business_id,
                    'numbering_type' => $type,
                    'prefix'         => 'DST-' . date('Y') . '-',
                    'starting_no'    => 1,
                    'current_no'     => 1,
                    'created_by'     => request()->session()->get('user.id'),
                ]);
            }

            $currentNo = $prefixRow->current_no ?: $prefixRow->starting_no;

            $ref_no = $prefixRow->prefix . $currentNo;

            $prefixRow->current_no = $currentNo + 1;
            $prefixRow->save();

            return $ref_no;
        });
    }

    /**
     * Show the Add Stock Transfer form
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        // Locations
        $locations        = BusinessLocation::forDropdown($business_id);
        $default_location = count($locations) > 0 ? array_key_first($locations->toArray()) : null;

        $ref_no = $this->peekRefNo($business_id);
        // Vehicles
        $vehicles = DistributionVehicles::where('business_id', $business_id)
            ->pluck('vehicle_no', 'id');

        // Product categories
        $categories_raw = Category::catAndSubCategories($business_id, 1);

        $categories = [];
        foreach ($categories_raw as $cat) {
            // Parent
            $categories[$cat['id']] = $cat['name'];

            // Subcategories
            if (! empty($cat['sub_categories'])) {
                foreach ($cat['sub_categories'] as $sub) {
                    $categories[$sub['id']] = '-- ' . $sub['name'];
                }
            }
        }

        // Routes
        $routes = Distribution_routes::where('business_id', $business_id)
            ->pluck('name', 'id');

        // Stores
        $stores = ! empty($default_location)
            ? Store::getStores($business_id, 0, $default_location)->pluck('name', 'id')
            : collect();

        // Default store = first store in list
        $default_store = count($stores) > 0 ? array_key_first($stores->toArray()) : null;
        $default_to_store = null;
        foreach ($stores as $store_id => $store_name) {
            if ((int) $store_id !== (int) $default_store) {
                $default_to_store = $store_id;
                break;
            }
        }

        // Units
        $units = Unit::forDropdown($business_id, false);

        return view('disstocktransfer::create')
            ->with(compact(
                'ref_no',
                'vehicles',
                'categories',
                'routes',
                'locations',
                'default_location',
                'stores',
                'default_store',
                'default_to_store',
                'units'
            ));
    }

    private function peekRefNo($business_id)
    {
        $type = 'stock_transfer';

        $row = DistributionNumberingPrefix::where('business_id', $business_id)
            ->where('numbering_type', $type)
            ->first();

        if (! $row) {
            return 'DST-' . date('Y') . '-1';
        }

        return $row->prefix . ($row->current_no ?: $row->starting_no);
    }

    /**
     * Store stock transfer
     */
    public function store(Request $request)
    {
        Log::info('stock transfer store request: ', $request->all());
        DB::beginTransaction();

        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id     = request()->session()->get('user.id');

            // Basic validation
            $validator = Validator::make($request->all(), [
                'date'                    => 'required|date',
                'vehicle_id'              => 'required|exists:distribution_vehicles,id',
                'location_id'             => 'required|exists:business_locations,id',
                'store_id'                => 'required|exists:stores,id',
                'to_store_id'             => 'required|exists:stores,id|different:store_id',
                'products'                => 'required|array|min:1',
                'products.*.variation_id' => 'required|exists:variations,id',
                'products.*.product_id'   => 'required|exists:products,id',
                'products.*.quantity'     => 'required|numeric|min:0.01',
                'products.*.unit_price'   => 'required|numeric|min:0',
                'products.*.unit_id'      => 'required|exists:units,id',
            ], [
                'date.required'                    => 'The transfer date is required.',
                'date.date'                        => 'Please enter a valid date.',
                'vehicle_id.required'              => 'Please select a vehicle.',
                'vehicle_id.exists'                => 'The selected vehicle is invalid.',
                'products.required'                => 'Please add at least one product to the transfer.',
                'products.min'                     => 'Please add at least one product to the transfer.',
                'products.*.variation_id.required' => 'Product information is missing.',
                'products.*.variation_id.exists'   => 'Invalid product selected.',
                'products.*.quantity.required'     => 'Quantity is required for all products.',
                'products.*.quantity.numeric'      => 'Quantity must be a number.',
                'products.*.quantity.min'          => 'Quantity must be at least :min.',
                'products.*.unit_price.required'   => 'Unit price is required.',
                'products.*.unit_price.numeric'    => 'Unit price must be a number.',
                'products.*.unit_price.min'        => 'Unit price must be at least :min.',
            ]);

            if ($validator->fails()) {
                $errorMessages = [];
                foreach ($validator->errors()->all() as $error) {
                    $errorMessages[] = $error;
                }

                // Return with the first error message for your notification system
                $output = [
                    'success' => false,
                    'msg'     => implode('<br>', $errorMessages),
                ];

                return redirect()->back()->withInput()->with('status', $output);
            }

            // Additional custom validation
            $errors = [];

            // Check if vehicle exists and belongs to business
            $vehicle = DistributionVehicles::where('id', $request->vehicle_id)
                ->where('business_id', $business_id)
                ->first();

            if (! $vehicle) {
                $errors[] = 'The selected vehicle does not exist or you do not have permission to use it.';
            }

            $sourceStore = Store::where('id', $request->store_id)
                ->where('business_id', $business_id)
                ->where('location_id', $request->location_id)
                ->first();

            $destinationStore = Store::where('id', $request->to_store_id)
                ->where('business_id', $business_id)
                ->where('location_id', $request->location_id)
                ->first();

            if (! $sourceStore) {
                $errors[] = 'The selected source store does not belong to the chosen location.';
            }

            if (! $destinationStore) {
                $errors[] = 'The selected destination store does not belong to the chosen location.';
            }

            // Validate stock availability for each product
            if (! empty($request->products)) {
                foreach ($request->products as $index => $product) {
                    $variationId = $product['variation_id'];
                    $quantity    = $product['quantity'];
                    $storeId     = $request->store_id;

                    // Check store stock availability
                    $availableQty = DB::table('variation_store_details')
                        ->where('variation_id', $variationId)
                        ->where('store_id', $storeId)
                        ->value('qty_available') ?? 0;

                    if ($availableQty < $quantity) {
                        // Get product name for better error message
                        $productName = Product::join('variations', 'products.id', '=', 'variations.product_id')
                            ->where('variations.id', $variationId)
                            ->value('products.name');

                        $errors[] = "Insufficient stock for {$productName}. Available: {$availableQty}, Requested: {$quantity}";
                    }
                }
            }

            // If there are custom validation errors
            if (! empty($errors)) {
                $output = [
                    'success' => false,
                    'msg'     => implode('<br>', $errors),
                ];

                return redirect()->back()->withInput()->with('status', $output);
            }

            $ref_no = $this->generateRefNo($business_id);
            Log::info('reference number: ' . $ref_no);

            // Create the stock transfer
            $transfer = DistributionStockTransfer::create([
                'business_id'         => $business_id,
                'reference_no'        => $ref_no,
                'date'                => $request->date,
                'vehicle_id'          => $request->vehicle_id,
                'location_id'         => $request->location_id,
                'store_id'            => $request->store_id,
                'to_store_id'         => $request->to_store_id,
                'product_category_id' => $request->product_category,
                'note'                => $request->notes,
                'status'              => 'pending',
                'created_by'          => $user_id,
            ]);

            // Save product lines
            $totalAmount = 0;
            foreach ($request->products as $line) {
                $lineTotal    = $line['quantity'] * $line['unit_price'];
                $totalAmount += $lineTotal;

                DistributionStockTransferLine::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id'        => $line['product_id'],
                    'variation_id'      => $line['variation_id'],
                    'unit_id'           => $line['unit_id'],
                    'unit_sale_price'   => $line['unit_price'],
                    'qty'               => $line['quantity'],
                    'subtotal'          => $lineTotal,
                ]);

                // Move stock between stores within the same location.
                $this->productUtil->decreaseProductQuantityStore(
                    $line['product_id'],
                    $line['variation_id'],
                    $request->location_id,
                    $line['quantity'],
                    $request->store_id,
                    'decrease',
                    0
                );

                $this->productUtil->updateProductQuantityStore(
                    $request->location_id,
                    $line['product_id'],
                    $line['variation_id'],
                    $line['quantity'],
                    $request->to_store_id,
                    0
                );
            }

            // Update transfer with total amount
            $transfer->update(['total_amount' => $totalAmount]);

            DB::commit();

            $output = [
                'success' => true,
                'msg'     => __('Stock transfer created successfully!'),
            ];

            return redirect()
                ->back()
                ->with('status', $output);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Stock Transfer Creation Failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            $output = [
                'success' => false,
                'msg'     => __('Failed to create stock transfer. Please try again.'),
            ];

            return redirect()
                ->back()
                ->withInput()
                ->with('status', $output);
        }
    }

    
    /**
     * Return stores filtered by location for use in AJAX location change.
     */
    public function getStoreByLocation(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->location_id;

        // Filter stores using existing helper
        $stores = \App\Store::getStores($business_id, 0, $location_id);

        return response()->json(['stores' => $stores]);
    }

    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        $transfer = DistributionStockTransfer::with(['lines.product', 'lines.variation'])
            ->where('business_id', $business_id)
            ->findOrFail($id);

        $locations = BusinessLocation::forDropdown($business_id);
        $vehicles = DistributionVehicles::where('business_id', $business_id)->pluck('vehicle_no', 'id');
        $categories_raw = Category::catAndSubCategories($business_id, 1);
        $categories = [];
        foreach ($categories_raw as $cat) {
            $categories[$cat['id']] = $cat['name'];
            if (! empty($cat['sub_categories'])) {
                foreach ($cat['sub_categories'] as $sub) {
                    $categories[$sub['id']] = '-- ' . $sub['name'];
                }
            }
        }

        $stores = Store::getStores($business_id, 0, $transfer->location_id)->pluck('name', 'id');
        $units = Unit::forDropdown($business_id, false);

        $line_items = $transfer->lines->map(function ($line) use ($transfer) {
            $source_qty = DB::table('variation_store_details')
                ->where('variation_id', $line->variation_id)
                ->where('store_id', $transfer->store_id)
                ->value('qty_available') ?? 0;
            $destination_qty = DB::table('variation_store_details')
                ->where('variation_id', $line->variation_id)
                ->where('store_id', $transfer->to_store_id)
                ->value('qty_available') ?? 0;

            return [
                'variation_id' => $line->variation_id,
                'product_id' => $line->product_id,
                'product_name' => $line->product->name ?? '',
                'variation_name' => $line->variation->name ?? '',
                'sub_sku' => $line->variation->sub_sku ?? '',
                'qty' => (float) $line->qty,
                'unit_price' => (float) $line->unit_sale_price,
                'unit_id' => $line->unit_id,
                'store_qty' => (float) $source_qty,
                'to_store_qty' => (float) $destination_qty,
            ];
        })->values();

        return view('disstocktransfer::edit')->with(compact(
            'transfer',
            'locations',
            'vehicles',
            'categories',
            'stores',
            'units',
            'line_items'
        ));
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $business_id = request()->session()->get('user.business_id');
            $user_id = request()->session()->get('user.id');

            $transfer = DistributionStockTransfer::with('lines')
                ->where('business_id', $business_id)
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'date'                    => 'required|date',
                'vehicle_id'              => 'required|exists:distribution_vehicles,id',
                'location_id'             => 'required|exists:business_locations,id',
                'store_id'                => 'required|exists:stores,id',
                'to_store_id'             => 'required|exists:stores,id|different:store_id',
                'products'                => 'required|array|min:1',
                'products.*.variation_id' => 'required|exists:variations,id',
                'products.*.product_id'   => 'required|exists:products,id',
                'products.*.quantity'     => 'required|numeric|min:0.01',
                'products.*.unit_price'   => 'required|numeric|min:0',
                'products.*.unit_id'      => 'required|exists:units,id',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withInput()->with('status', [
                    'success' => false,
                    'msg' => implode('<br>', $validator->errors()->all()),
                ]);
            }

            // Revert old movement first (inside transaction).
            foreach ($transfer->lines as $old_line) {
                $to_store_available = DB::table('variation_store_details')
                    ->where('variation_id', $old_line->variation_id)
                    ->where('store_id', $transfer->to_store_id)
                    ->value('qty_available') ?? 0;

                if ((float) $to_store_available < (float) $old_line->qty) {
                    throw new \Exception('Cannot edit transfer because destination store stock has already been used for one or more items.');
                }

                // Return old qty back to source store.
                $this->productUtil->updateProductQuantityStore(
                    $transfer->location_id,
                    $old_line->product_id,
                    $old_line->variation_id,
                    $old_line->qty,
                    $transfer->store_id
                );

                // Remove old qty from destination store.
                $this->productUtil->decreaseProductQuantityStore(
                    $old_line->product_id,
                    $old_line->variation_id,
                    $transfer->location_id,
                    $old_line->qty,
                    $transfer->to_store_id,
                    'decrease',
                    0
                );
            }

            DistributionStockTransferLine::where('stock_transfer_id', $transfer->id)->delete();

            // Validate current availability for new request.
            foreach ((array) $request->products as $line) {
                $available_qty = DB::table('variation_store_details')
                    ->where('variation_id', $line['variation_id'])
                    ->where('store_id', $request->store_id)
                    ->value('qty_available') ?? 0;

                if ((float) $available_qty < (float) $line['quantity']) {
                    throw new \Exception('Insufficient stock for one or more products in the selected source store.');
                }
            }

            $transfer->update([
                'date' => $request->date,
                'vehicle_id' => $request->vehicle_id,
                'location_id' => $request->location_id,
                'store_id' => $request->store_id,
                'to_store_id' => $request->to_store_id,
                'product_category_id' => $request->product_category,
                'note' => $request->notes,
                'created_by' => $user_id,
            ]);

            $totalAmount = 0;
            foreach ((array) $request->products as $line) {
                $lineTotal = (float) $line['quantity'] * (float) $line['unit_price'];
                $totalAmount += $lineTotal;

                DistributionStockTransferLine::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $line['product_id'],
                    'variation_id' => $line['variation_id'],
                    'unit_id' => $line['unit_id'],
                    'unit_sale_price' => $line['unit_price'],
                    'qty' => $line['quantity'],
                    'subtotal' => $lineTotal,
                ]);

                $this->productUtil->decreaseProductQuantityStore(
                    $line['product_id'],
                    $line['variation_id'],
                    $request->location_id,
                    $line['quantity'],
                    $request->store_id,
                    'decrease',
                    0
                );

                $this->productUtil->updateProductQuantityStore(
                    $request->location_id,
                    $line['product_id'],
                    $line['variation_id'],
                    $line['quantity'],
                    $request->to_store_id
                );
            }

            $transfer->update(['total_amount' => $totalAmount]);
            DB::commit();

            return redirect()->route('disstocktransfer.dis-stock-transfer.index')->with('status', [
                'success' => true,
                'msg' => __('Stock transfer updated successfully!'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock Transfer Update Failed: ' . $e->getMessage());

            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => $e->getMessage() ?: __('Failed to update stock transfer. Please try again.'),
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $business_id = request()->session()->get('user.business_id');
            $transfer = DistributionStockTransfer::with('lines')
                ->where('business_id', $business_id)
                ->findOrFail($id);

            foreach ($transfer->lines as $line) {
                $to_store_available = DB::table('variation_store_details')
                    ->where('variation_id', $line->variation_id)
                    ->where('store_id', $transfer->to_store_id)
                    ->value('qty_available') ?? 0;

                if ((float) $to_store_available < (float) $line->qty) {
                    throw new \Exception('Cannot delete transfer because destination store stock has already been used.');
                }

                $this->productUtil->updateProductQuantityStore(
                    $transfer->location_id,
                    $line->product_id,
                    $line->variation_id,
                    $line->qty,
                    $transfer->store_id
                );

                $this->productUtil->decreaseProductQuantityStore(
                    $line->product_id,
                    $line->variation_id,
                    $transfer->location_id,
                    $line->qty,
                    $transfer->to_store_id,
                    'decrease',
                    0
                );
            }

            DistributionStockTransferLine::where('stock_transfer_id', $transfer->id)->delete();
            $transfer->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('Stock transfer deleted successfully!'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'msg' => $e->getMessage() ?: __('Failed to delete stock transfer.'),
            ]);
        }
    }

    public function searchProduct(Request $request)
    {
        if ($request->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $term        = $request->input('query');
            $store_id    = $request->store_id;
            $to_store_id = $request->to_store_id;

            if (empty($term)) {
                return response()->json([]);
            }

            $query = Product::leftJoin('variations', 'products.id', '=', 'variations.product_id')
                ->leftJoin('units', 'products.unit_id', '=', 'units.id') // Join units table
                ->where(function ($q) use ($term) {
                    $q->where('products.name', 'like', "%{$term}%");
                    $q->orWhere('products.sku', 'like', "%{$term}%");
                    $q->orWhere('variations.sub_sku', 'like', "%{$term}%");
                    $q->orWhere('variations.name', 'like', "%{$term}%");
                })
                ->where('products.business_id', $business_id)
                ->whereNull('variations.deleted_at')
                ->select(
                    'products.id as product_id',
                    'products.name',
                    'products.type',
                    'products.unit_id',
                    'units.short_name as unit_name',
                    'variations.id as variation_id',
                    'variations.name as variation_name',
                    'variations.sub_sku as sub_sku',
                    'variations.sell_price_inc_tax as unit_price'
                )
                ->groupBy('variation_id')
                ->limit(25);

            $products = $query->get();

            $result = [];

            foreach ($products as $p) {

                // Get Store Qty
                $store_qty = DB::table('variation_store_details')
                    ->where('variation_id', $p->variation_id)
                    ->where('store_id', $store_id)
                    ->value('qty_available') ?? 0;

                // Get destination store qty
                $to_store_qty = DB::table('variation_store_details')
                    ->where('variation_id', $p->variation_id)
                    ->where('store_id', $to_store_id)
                    ->value('qty_available') ?? 0;

                $text = $p->name;
                if ($p->type == 'variable' && $p->variation_name != 'DUMMY') {
                    $text .= " ({$p->variation_name})";
                }

                $result[] = [
                    'id'           => $p->variation_id,
                    'text'         => $text . ' - ' . $p->sub_sku,
                    'product_id'   => $p->product_id,
                    'variation_id' => $p->variation_id,
                    'sub_sku'      => $p->sub_sku,
                    'unit_price'   => $p->unit_price,
                    'store_qty'    => $store_qty,
                    'to_store_qty' => $to_store_qty,
                    'unit_id'      => $p->unit_id,
                    'unit_name'    => $p->unit_name,
                ];
            }

            return response()->json($result);
        }
    }

    /**
     * Return vehicle quantity for a given variation.
     * 
     * Used by JS when the selected vehicle changes so that existing rows
     * can display the updated available qty on that vehicle.
     */
    public function getStoreQty(Request $request)
    {
        $store_id = $request->store_id;
        $variation_id = $request->variation_id;
        $qty = DB::table('variation_store_details')
            ->where('store_id', $store_id)
            ->where('variation_id', $variation_id)
            ->value('qty_available') ?? 0;
        return response()->json(['qty' => $qty]);
    }

    public function productWiseList(Request $request)
    {
        if ($request->ajax()) {
            $business_id = $request->session()->get('user.business_id');

            $query = DistributionStockTransferLine::join('distribution_stock_transfers as dst', 'dst.id', '=', 'distribution_stock_transfer_lines.stock_transfer_id')
                ->join('products as p', 'p.id', '=', 'distribution_stock_transfer_lines.product_id')
                ->leftJoin('variations as v', 'v.id', '=', 'distribution_stock_transfer_lines.variation_id')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'dst.location_id')
                ->leftJoin('stores as s', 's.id', '=', 'dst.store_id')
                ->leftJoin('distribution_vehicles as dv', 'dv.id', '=', 'dst.vehicle_id')
                ->where('dst.business_id', $business_id)
                ->select([
                    'dst.id as transfer_id',
                    'dst.date',
                    'dst.reference_no',
                    'bl.name as location_name',
                    'v.sub_sku as product_code', // Product Code
                    DB::raw('CONCAT(p.name, IF(v.name != "DUMMY" AND v.name IS NOT NULL, CONCAT(" (", v.name, ")"), "")) as product_name'),
                    'distribution_stock_transfer_lines.qty as quantity',
                    's.name as store_name',
                    'dv.vehicle_no',
                    'distribution_stock_transfer_lines.subtotal as total_amount',
                ]);

            return Datatables::of($query)
                ->editColumn('date', function ($row) {
                    return \Carbon\Carbon::parse($row->date)->format('Y-m-d');
                })
                ->editColumn('quantity', function ($row) {
                    return number_format($row->quantity, 2);
                })
                ->editColumn('total_amount', function ($row) {
                    return number_format($row->total_amount, 2);
                })
                ->addColumn('action', function ($row) {
                    return '<button
                        data-href="' . route('disstocktransfer.dis-stock-transfer.show', $row->transfer_id) . '"
                        class="btn btn-xs btn-primary view-transfer">
                        <i class="fa fa-eye"></i>
                    </button>';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function show($id)
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $transfer = DistributionStockTransfer::with([
                'lines',
                'lines.product',
                'lines.variation',
                'location',
                'store',
                'toStore',
                'vehicle',
            ])
                ->where('business_id', $business_id)
                ->findOrFail($id);

            return view('disstocktransfer::partials.show_modal', compact('transfer'));
        }

        abort(404);
    }

}
