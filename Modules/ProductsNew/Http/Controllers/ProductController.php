<?php
namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Http\Requests\StoreProductRequest;
use Modules\ProductsNew\Http\Requests\UpdateProductRequest;
use Modules\ProductsNew\Services\ProductActionService;
use Modules\ProductsNew\Services\ProductLookupService;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;
use Modules\ProductsNew\Services\ProductQueryService;
use Modules\ProductsNew\Services\ProductWriteService;

class ProductController extends Controller
{
    public function __construct(
        protected ProductQueryService $query,
        protected ProductWriteService $write,
        protected ProductLookupService $lookup,
        protected ProductActionService $actions
    ) {}

    public function index(Request $request)
    {
        $filters = collect($request->only([
            'search',
            'category_id',
            'sub_category_id',
            'brand_id',
            'status',
            'stock_type',
        ]))->map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        })->toArray();

        $canViewPurchasePrice = $this->canViewPurchasePrice($request);
        $products = $this->query->paginated($filters, 25, $canViewPurchasePrice);

        // 8033: auto-filter requests update only the product result region.
        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('productsnew::products.partials.table', compact(
                    'products',
                    'canViewPurchasePrice'
                ))->render(),
                'pagination_html' => $products->links()->render(),
                'total_label' => number_format($products->total()) . ' records',
                'url' => $request->fullUrl(),
            ]);
        }

        // Lookup lists are needed only for the complete page, not every filter request.
        $lookups = $this->lookup->formLookups();

        return view('productsnew::products.index', compact(
            'products',
            'lookups',
            'filters',
            'canViewPurchasePrice'
        ));
    }

    public function data(Request $request)
    {
        $filters = $request->only([
            'search', 'category_id', 'sub_category_id', 'brand_id', 'status', 'stock_type',
        ]);

        return response()->json(
            $this->query->paginated($filters, 25, $this->canViewPurchasePrice($request))
        );
    }

    public function create()
    {
        $product = new ProductsNewProduct([
            'type' => 'single',
            'enable_stock' => 1,
            'tax_type' => 'exclusive',
        ]);
        $lookups = $this->lookup->formLookups();
        $formState = [
            'pricing' => [
                'single_dpp' => null,
                'single_dpp_inc_tax' => null,
                'profit_percent' => null,
                'profit_basis' => 'inclusive',
                'single_dsp' => null,
                'single_dsp_inc_tax' => null,
            ],
            'locations' => [],
        ];

        return view('productsnew::products.create', compact('product', 'lookups', 'formState'));
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->write->create($request->validated());

        return redirect()
            ->route('products-new.products.show', $product->id)
            ->with('status', __('productsnew::product.product_created'));
    }

    public function show(ProductsNewProduct $product)
    {
        $detail = $this->query->findForView($product->id) ?: $product;

        return view('productsnew::products.show', ['product' => $product, 'detail' => $detail]);
    }

    public function edit(ProductsNewProduct $product)
    {
        $lookups = $this->lookup->formLookups();
        $formState = $this->lookup->productFormState((int) $product->id);

        /*
         * MA-002 (IS-1919 #4): fields that must not change once stock has moved.
         *
         * Category, Sub Category, Unit, Stock Account and Selling Price Tax all
         * decide how existing purchase and sale lines are interpreted. Changing
         * a unit or a stock account after the product has been bought and sold
         * silently rewrites what those lines mean and the ledger stops
         * reconciling - with nothing on screen to say it happened.
         *
         * So they stay editable until the product is used, and are locked
         * afterwards. Every other field on the form is untouched.
         */
        $usedInTransactions = $this->actions->isUsedInTransactions(
            (int) $product->id,
            ProductsNewTenantGuard::businessId()
        );

        return view('productsnew::products.edit', compact('product', 'lookups', 'formState', 'usedInTransactions'));
    }

    public function update(UpdateProductRequest $request, ProductsNewProduct $product)
    {
        $this->write->update($product, $request->validated());

        return redirect()
            ->route('products-new.products.show', $product->id)
            ->with('status', __('productsnew::product.product_updated'));
    }

    public function status(Request $request, ProductsNewProduct $product)
    {
        $data = $request->validate([
            'active' => 'required|boolean',
            'note' => 'nullable|string|max:500',
        ]);

        $active = (bool) $data['active'];

        try {
            $this->actions->setActive($product, $active, $data['note'] ?? null);
        } catch (DomainException $exception) {
            return redirect()
                ->route('products-new.products.index')
                ->withErrors([$exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return redirect()
                ->route('products-new.products.index')
                ->withErrors([
                    'This product could not be deleted because another system record still refers to it. Deactivate the product instead.',
                ]);
        }

        return redirect()
            ->route('products-new.products.index')
            ->with('status', $active ? 'Product activated successfully.' : 'Product deactivated successfully.');
    }

    public function destroy(ProductsNewProduct $product)
    {
        $name = (string) $product->name;

        try {
            $this->actions->delete($product);
        } catch (DomainException $exception) {
            return redirect()
                ->route('products-new.products.index')
                ->withErrors([$exception->getMessage()]);
        }

        return redirect()
            ->route('products-new.products.index')
            ->with('status', 'Product "' . $name . '" deleted successfully.');
    }

    /**
     * Purchase price is commercially sensitive. Keep the permission check in
     * the controller so both the Blade page and the JSON endpoint follow the
     * same rule. The legacy permission is accepted for installations that
     * already use the main Product module's purchase-price permission.
     */
    private function canViewPurchasePrice(Request $request): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $user->can('products_new.purchase_price.view')
            || $user->can('view_purchase_price');
    }
}
