<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BankingMicrofinance\Entities\Collection;
use Modules\BankingMicrofinance\Entities\Loan;
use Modules\BankingMicrofinance\Entities\Installment;
use Modules\BankingMicrofinance\Services\NumberGenerator;

class CollectionController extends Controller
{
    public function index(){ $collections=Collection::where('business_id',$this->businessId())->latest()->paginate(25); return view('bankingmicrofinance::collections.index', compact('collections')); }
    public function create(){ $collection=new Collection(['collection_date'=>today()->toDateString(),'payment_method'=>'cash']); $loans=Loan::where('business_id',$this->businessId())->whereIn('status',['disbursed','active'])->orderByDesc('id')->get(); return view('bankingmicrofinance::collections.form', compact('collection','loans')); }
    public function store(Request $request, NumberGenerator $numbers){
        $data=$request->validate(['loan_id'=>'required|exists:bkg_mfi_loans,id','collection_date'=>'required|date','principal_paid'=>'nullable|numeric','interest_paid'=>'nullable|numeric','fee_paid'=>'nullable|numeric','saving_paid'=>'nullable|numeric','payment_method'=>'required|string|max:50','note'=>'nullable|string']);
        $loan=Loan::findOrFail($data['loan_id']); $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId(); $data['member_id']=$loan->member_id; $data['receipt_no']=$numbers->next('bkg_mfi_collections','receipt_no',config('bankingmicrofinance.number_prefixes.receipt','MFR')); $data['created_by']=auth()->id(); $data['principal_paid']=$data['principal_paid']??0; $data['interest_paid']=$data['interest_paid']??0; $data['fee_paid']=$data['fee_paid']??0; $data['saving_paid']=$data['saving_paid']??0; $data['total_paid']=$data['principal_paid']+$data['interest_paid']+$data['fee_paid']+$data['saving_paid'];
        DB::transaction(function() use($data,$loan){ Collection::create($data); $remaining=$data['principal_paid']+$data['interest_paid']+$data['fee_paid']; $installments=Installment::where('loan_id',$loan->id)->where('status','!=','paid')->orderBy('installment_no')->get(); foreach($installments as $inst){ if($remaining<=0) break; $pay=min($remaining, max(0,$inst->total_due-$inst->paid_amount)); $inst->paid_amount += $pay; $inst->status = $inst->paid_amount >= $inst->total_due ? 'paid' : 'partial'; $inst->save(); $remaining -= $pay; } if(Installment::where('loan_id',$loan->id)->where('status','!=','paid')->count()===0){ $loan->update(['status'=>'settled']); } else { $loan->update(['status'=>'active']); }});
        return redirect()->route('banking.microfinance.collections.index')->with('status','Collection saved and schedule updated.');
    }
    public function receipt(Collection $collection){ return view('bankingmicrofinance::collections.receipt', compact('collection')); }
}
