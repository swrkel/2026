<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Entities\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::forBusiness($this->businessId())->latest()->paginate(25);
        return view('bankinginsurance::settings.products', compact('products'));
    }

    public function create()
    {
        $product = new Product();
        return view('bankinginsurance::settings.product_form', compact('product'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['business_id'] = $this->businessId();
        $data['created_by'] = $this->userId();
        Product::create($data);
        return redirect()->route('banking-insurance.products.index')->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function edit(Product $product)
    {
        abort_unless($product->business_id == $this->businessId(), 403);
        return view('bankinginsurance::settings.product_form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->business_id == $this->businessId(), 403);
        $product->update($this->validateData($request));
        return redirect()->route('banking-insurance.products.index')->with('status', ['success' => 1, 'msg' => __('lang_v1.updated_success')]);
    }

    public function destroy(Product $product)
    {
        abort_unless($product->business_id == $this->businessId(), 403);
        $product->update(['is_active' => false]);
        return back()->with('status', ['success' => 1, 'msg' => __('lang_v1.deleted_success')]);
    }

    private function validateData(Request $request)
    {
        return $request->validate([
            'location_id' => 'nullable|integer',
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:60',
            'insurance_type' => 'required|string|max:60',
            'default_sum_assured' => 'nullable|numeric|min:0',
            'default_premium' => 'nullable|numeric|min:0',
            'commission_rate' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
