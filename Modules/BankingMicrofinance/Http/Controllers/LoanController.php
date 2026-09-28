<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BankingMicrofinance\Entities\Loan;
use Modules\BankingMicrofinance\Entities\Member;
use Modules\BankingMicrofinance\Entities\LoanProduct;
use Modules\BankingMicrofinance\Entities\Installment;
use Modules\BankingMicrofinance\Services\LoanCalculator;
use Modules\BankingMicrofinance\Services\NumberGenerator;

class LoanController extends Controller
{
    public function index(){ $loans = Loan::where('business_id',$this->businessId())->latest()->paginate(25); return view('bankingmicrofinance::loans.index', compact('loans')); }
    public function create(){ $loan = new Loan(['status'=>'draft','application_date'=>today()->toDateString()]); $members=Member::where('business_id',$this->businessId())->orderBy('name')->get(); $products=LoanProduct::where('business_id',$this->businessId())->where('is_active',1)->orderBy('name')->get(); return view('bankingmicrofinance::loans.form', compact('loan','members','products')); }
    public function store(Request $request, NumberGenerator $numbers, LoanCalculator $calculator){
        $data=$this->validated($request); $product = LoanProduct::find($data['loan_product_id'] ?? null); $rate = $product->annual_interest_rate ?? (float)($request->annual_interest_rate ?? 0); $method=$product->interest_method ?? 'flat'; $totals=$calculator->totals((float)$data['principal_amount'],$rate,(int)$data['term_weeks'],$method);
        $member=Member::findOrFail($data['member_id']); $data=array_merge($data,$totals); $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId(); $data['group_id']=$member->group_id; $data['loan_no']=$numbers->next('bkg_mfi_loans','loan_no',config('bankingmicrofinance.number_prefixes.loan','MFL')); $data['created_by']=auth()->id(); $data['status']='created';
        DB::transaction(function() use($data,$calculator){ $loan=Loan::create($data); foreach($calculator->schedule($loan->principal_amount,$loan->interest_amount,$loan->term_weeks,$loan->application_date) as $row){ $row['loan_id']=$loan->id; Installment::create($row); }});
        return redirect()->route('banking.microfinance.loans.index')->with('status','Loan application created with schedule.');
    }
    public function show(Loan $loan){ $installments=Installment::where('loan_id',$loan->id)->orderBy('installment_no')->get(); return view('bankingmicrofinance::loans.show', compact('loan','installments')); }
    public function approve(Request $request, Loan $loan){ $loan->update(['status'=>'approved','approved_date'=>today(),'approved_by'=>auth()->id(),'approval_note'=>$request->approval_note]); return back()->with('status','Loan approved.'); }
    public function disburse(Loan $loan){ $loan->update(['status'=>'disbursed','disbursed_date'=>today()]); return back()->with('status','Loan disbursed.'); }
    private function validated(Request $request): array { return $request->validate(['member_id'=>'required|exists:bkg_mfi_members,id','loan_product_id'=>'nullable|exists:bkg_mfi_loan_products,id','application_date'=>'nullable|date','principal_amount'=>'required|numeric|min:0','term_weeks'=>'required|integer|min:1','purpose'=>'nullable|string']); }
}
