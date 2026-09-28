<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Models\DepositProduct;

class DepositProductController extends Controller
{
    public function index()
    {
        $products = Schema::hasTable('deposit_products') ? DepositProduct::latest()->paginate(20) : collect();
        return view('deposits::products.index', compact('products'));
    }

    public function create()
    {
        return view('deposits::products.form', ['product' => new DepositProduct(['status' => 'active', 'interest_frequency' => 'monthly', 'interest_method' => 'simple', 'renewal_policy' => 'manual']), 'action' => route('deposits.products.store')]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['business_id'] = session('business.id');
        $data['created_by'] = auth()->id();
        DepositProduct::create($data);
        return redirect()->route('deposits.products.index')->with('status', ['success' => 1, 'msg' => __('deposits::lang.product_saved')]);
    }

    public function edit($id)
    {
        $product = DepositProduct::findOrFail($id);
        return view('deposits::products.form', ['product' => $product, 'action' => route('deposits.products.update', $id)]);
    }

    public function update(Request $request, $id)
    {
        $product = DepositProduct::findOrFail($id);
        $data = $this->validatedData($request);
        $data['updated_by'] = auth()->id();
        $product->update($data);
        return redirect()->route('deposits.products.index')->with('status', ['success' => 1, 'msg' => __('deposits::lang.product_saved')]);
    }

    public function destroy($id)
    {
        DepositProduct::findOrFail($id)->delete();
        return redirect()->route('deposits.products.index')->with('status', ['success' => 1, 'msg' => __('deposits::lang.product_deleted')]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:191',
            'code' => 'nullable|string|max:50',
            'account_prefix' => 'nullable|string|max:20',
            'type' => 'required|string|max:50',
            'interest_rate' => 'nullable|numeric|min:0',
            'term_months' => 'nullable|integer|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'interest_frequency' => 'nullable|string|max:50',
            'interest_method' => 'nullable|string|max:50',
            'renewal_policy' => 'nullable|string|max:50',
            'premature_closure_allowed' => 'nullable|boolean',
            'penalty_rate' => 'nullable|numeric|min:0',
            'require_nominee' => 'nullable|boolean',
            'require_beneficiary' => 'nullable|boolean',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
        ]);
    }
}
