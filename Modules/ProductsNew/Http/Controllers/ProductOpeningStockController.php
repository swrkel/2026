<?php

namespace Modules\ProductsNew\Http\Controllers;

use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Services\ProductOpeningStockService;

class ProductOpeningStockController extends Controller
{
    public function __construct(protected ProductOpeningStockService $service)
    {
    }

    public function edit(ProductsNewProduct $product)
    {
        return view('productsnew::products.opening_stock', $this->service->screenData($product));
    }

    public function update(Request $request, ProductsNewProduct $product)
    {
        $data = $request->validate([
            'variation_id' => 'required|integer',
            'location_id' => 'required|integer',
            'store_id' => 'nullable|integer',
            'quantity' => 'required|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'opening_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $result = $this->service->update($product, $data);
        } catch (DomainException $exception) {
            return back()->withInput()->withErrors([$exception->getMessage()]);
        } catch (QueryException $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'Opening stock could not be saved because the tenant stock tables are not compatible with this entry. No stock change was committed.',
            ]);
        }

        return redirect()
            ->route('products-new.products.opening-stock.edit', $product->id)
            ->with('status', $result['message']);
    }
}
