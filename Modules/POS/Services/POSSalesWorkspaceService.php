<?php

namespace Modules\POS\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class POSSalesWorkspaceService extends POSBaseService
{
    public function __construct(private POSCustomersModuleBridgeService $customersBridge) {}
    public function getContext(Request $request): array
    {
        return [
            'business_id' => $this->businessId(),
            'business_location_id' => $request->session()->get('user.business_location_id') ?? $request->input('location_id'),
            'cashier_id' => auth()->id(),
            'cashier_name' => optional(auth()->user())->first_name ?? optional(auth()->user())->username ?? 'Cashier',
            'business_date' => now()->format('Y-m-d'),
            'business_time' => now()->format('H:i'),
            'register' => $this->currentRegister(),
            'shift' => $this->currentShift(),
        ];
    }

    public function quickActions(): array
    {
        return [
            ['key' => 'new_sale', 'label' => __('pos::page_003.new_sale'), 'icon' => 'fa-plus-circle'],
            ['key' => 'quotation', 'label' => __('pos::page_003.quotation'), 'icon' => 'fa-file-text-o'],
            ['key' => 'hold', 'label' => __('pos::page_003.hold'), 'icon' => 'fa-pause-circle'],
            ['key' => 'resume', 'label' => __('pos::page_003.resume'), 'icon' => 'fa-play-circle'],
            ['key' => 'suspend', 'label' => __('pos::page_003.suspend'), 'icon' => 'fa-clock-o'],
            ['key' => 'return', 'label' => __('pos::page_003.return'), 'icon' => 'fa-undo'],
            ['key' => 'exchange', 'label' => __('pos::page_003.exchange'), 'icon' => 'fa-exchange'],
            ['key' => 'customer', 'label' => __('pos::page_003.customer'), 'icon' => 'fa-user'],
            ['key' => 'product', 'label' => __('pos::page_003.product'), 'icon' => 'fa-cubes'],
            ['key' => 'price_check', 'label' => __('pos::page_003.price_check'), 'icon' => 'fa-search'],
        ];
    }

    public function productFilters(Request $request): array
    {
        return [
            'categories' => $this->safePluck('pos_categories', 'name', 'id'),
            'brands' => $this->safePluck('pos_brands', 'name', 'id'),
        ];
    }

    public function paymentMethods(Request $request): array
    {
        $defaults = ['Cash', 'Visa', 'MasterCard', 'Bank Transfer', 'Cheque', 'Wallet', 'Gift Voucher', 'Gift Card', 'Customer Credit', 'Loyalty Redemption'];
        return collect($defaults)->map(fn ($name, $i) => ['id' => $i + 1, 'name' => $name])->all();
    }

    public function searchProducts(Request $request): array
    {
        if (! $this->tableExists('pos_products')) {
            return ['data' => [], 'message' => __('pos::page_003.products_table_not_available')];
        }

        $columns = $this->columns('pos_products');
        $businessId = $this->businessId();
        $term = trim((string) $request->input('q'));
        $query = $this->connection()->table('pos_products');

        if (in_array('business_id', $columns, true) && $businessId !== null) {
            $query->where(function ($w) use ($businessId) {
                $w->whereNull('business_id')->orWhere('business_id', $businessId);
            });
        }
        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('deleted_at');
        }
        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', 1);
        }

        $searchColumns = array_values(array_intersect(['name', 'sku', 'barcode'], $columns));
        if ($term !== '' && $searchColumns !== []) {
            $like = '%' . $term . '%';
            $query->where(function ($q) use ($like, $searchColumns) {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $q->where($column, 'like', $like);
                    } else {
                        $q->orWhere($column, 'like', $like);
                    }
                }
            });
        }

        if ($request->filled('category_id') && in_array('category_id', $columns, true)) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('brand_id') && in_array('brand_id', $columns, true)) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        $priceColumns = ['sell_price', 'selling_price', 'default_sell_price', 'unit_price', 'price'];
        $stockColumns = ['stock_qty', 'stock_quantity', 'current_stock', 'qty_available'];

        return ['data' => $query->orderBy(in_array('name', $columns, true) ? 'name' : 'id')->limit(30)->get()->map(function ($product) use ($priceColumns, $stockColumns) {
            $price = 0.0;
            foreach ($priceColumns as $column) {
                if (property_exists($product, $column) && $product->{$column} !== null) {
                    $price = (float) $product->{$column};
                    break;
                }
            }

            $stock = 0.0;
            foreach ($stockColumns as $column) {
                if (property_exists($product, $column) && $product->{$column} !== null) {
                    $stock = (float) $product->{$column};
                    break;
                }
            }

            return [
                'id' => $product->id,
                'name' => $product->name ?? 'Item',
                'sku' => $product->sku ?? '',
                'barcode' => $product->barcode ?? '',
                'image' => $product->image ?? null,
                'price' => $price,
                'stock' => $stock,
            ];
        })->all()];
    }

    public function searchCustomers(Request $request): array
    {
        // Customer master comes from the standalone Customers module.
        // POS no longer uses or duplicates pos_customers for customer selection.
        return $this->customersBridge->search($request, 30);
    }

    public function priceCheck(Request $request): array
    {
        $productId = $request->input('product_id');
        return [
            'product_id' => $productId,
            'price' => $productId ? $this->productPrice($productId) : 0,
            'stock' => $productId ? $this->productStock($productId) : 0,
        ];
    }

    private function currentRegister(): ?object
    {
        return Schema::hasTable('pos_registers') ? DB::table('pos_registers')->where('is_active', 1)->orderByDesc('id')->first() : null;
    }

    private function currentShift(): ?object
    {
        return Schema::hasTable('pos_register_sessions') ? DB::table('pos_register_sessions')->where('status', 'open')->orderByDesc('id')->first() : null;
    }

    private function safePluck(string $table, string $label, string $value): array
    {
        return Schema::hasTable($table) ? DB::table($table)->orderBy($label)->pluck($label, $value)->toArray() : [];
    }

    private function productPrice($productId): float
    {
        $column = $this->firstExistingColumn('pos_products', ['sell_price', 'selling_price', 'default_sell_price', 'unit_price', 'price']);
        return $column ? (float) $this->connection()->table('pos_products')->where('id', $productId)->value($column) : 0.0;
    }

    private function productStock($productId): float
    {
        $column = $this->firstExistingColumn('pos_products', ['stock_qty', 'stock_quantity', 'current_stock', 'qty_available']);
        return $column ? (float) $this->connection()->table('pos_products')->where('id', $productId)->value($column) : 0.0;
    }
}
