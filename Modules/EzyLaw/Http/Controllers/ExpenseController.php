<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\{LawExpense,LawMatter}; use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard}; use Modules\EzyLaw\Contracts\FinanceGateway;
class ExpenseController extends Controller
{
 public function index(){return view('ezylaw::expenses.index',['expenses'=>LawExpense::with('matter')->orderByDesc('expense_date')->paginate(25),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get()]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'nullable|integer','expense_date'=>'required|date','category'=>'required|string|max:100','description'=>'required|string|max:1000','amount'=>'required|numeric|min:0','billable'=>'nullable|boolean']);EzyLawReferenceGuard::matter($d['matter_id']??null);$d['billable']=$r->boolean('billable');$d['invoiced']=0;$d['finance_sync_status']='pending';LawExpense::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'created_by'=>auth()->id()]);return back()->with('success','Expense saved.');}
 public function sync(LawExpense $expense,FinanceGateway $finance){$result=$finance->syncExpense($expense->id);$expense->update(['finance_sync_status'=>$result['status']]);return back()->with($result['success']?'success':'warning',$result['message']);}
}
