<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingMicrofinance\Entities\Loan;
use Modules\BankingMicrofinance\Entities\Collection;
use Modules\BankingMicrofinance\Entities\Installment;

class ReportController extends Controller
{
    public function portfolio(Request $request){ $loans=Loan::where('business_id',$this->businessId())->whereIn('status',['disbursed','active'])->latest()->paginate(50); return view('bankingmicrofinance::reports.portfolio', compact('loans')); }
    public function arrears(Request $request){ $installments=Installment::whereDate('due_date','<',today())->where('status','!=','paid')->orderBy('due_date')->paginate(50); return view('bankingmicrofinance::reports.arrears', compact('installments')); }
    public function collections(Request $request){ $collections=Collection::where('business_id',$this->businessId())->when($request->from,fn($q)=>$q->whereDate('collection_date','>=',$request->from))->when($request->to,fn($q)=>$q->whereDate('collection_date','<=',$request->to))->latest()->paginate(50); return view('bankingmicrofinance::reports.collections', compact('collections')); }
}
