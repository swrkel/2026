<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingMicrofinance\Entities\LoanProduct;

class LoanProductController extends Controller
{
    public function index(){ $products=LoanProduct::where('business_id',$this->businessId())->latest()->paginate(25); return view('bankingmicrofinance::settings.products', compact('products')); }
    public function create(){ $product=new LoanProduct(['is_active'=>1,'interest_method'=>'flat']); return view('bankingmicrofinance::settings.product_form', compact('product')); }
    public function store(Request $request){ $data=$this->validated($request); $data['business_id']=$this->businessId(); LoanProduct::create($data); return redirect()->route('banking.microfinance.products.index')->with('status','Loan product created.'); }
    public function edit(LoanProduct $product){ return view('bankingmicrofinance::settings.product_form', compact('product')); }
    public function update(Request $request, LoanProduct $product){ $product->update($this->validated($request)); return redirect()->route('banking.microfinance.products.index')->with('status','Loan product updated.'); }
    private function validated(Request $request): array { return $request->validate(['code'=>'required|string|max:40','name'=>'required|string|max:255','min_amount'=>'nullable|numeric','max_amount'=>'nullable|numeric','annual_interest_rate'=>'nullable|numeric','default_term_weeks'=>'nullable|integer','interest_method'=>'required|in:flat,declining','is_active'=>'nullable|boolean']); }
}
