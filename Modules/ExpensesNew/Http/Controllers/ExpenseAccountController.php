<?php

namespace Modules\ExpensesNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ExpensesNew\Entities\ExpenseAccount;
use Modules\ExpensesNew\Services\ExpenseAccountService;
use Modules\ExpensesNew\Utils\BusinessScope;

class ExpenseAccountController extends Controller
{
    public function index(){ return view('expensesnew::accounts.index'); }
    public function create(){ return view('expensesnew::accounts.form', ['account' => new ExpenseAccount()]); }
    public function store(Request $request){
        $request->validate(['name' => 'required|max:191', 'code' => 'nullable|max:50']);
        ExpenseAccount::create($request->only(['name','code','description']) + ['business_id'=>BusinessScope::businessId(),'is_active'=>$request->has('is_active')?1:1,'created_by'=>auth()->id()]);
        return redirect()->route('expensesnew.accounts.index')->with('status','Expense account saved');
    }
    public function edit($id){ $account=ExpenseAccount::where('business_id',BusinessScope::businessId())->findOrFail($id); return view('expensesnew::accounts.form', compact('account')); }
    public function update(Request $request,$id){
        $account=ExpenseAccount::where('business_id',BusinessScope::businessId())->findOrFail($id);
        $account->update($request->only(['name','code','description']) + ['is_active'=>$request->has('is_active')?1:0,'updated_by'=>auth()->id()]);
        return redirect()->route('expensesnew.accounts.index')->with('status','Expense account updated');
    }
    public function destroy($id){ ExpenseAccount::where('business_id',BusinessScope::businessId())->findOrFail($id)->delete(); return response()->json(['success'=>true]); }
    public function sync(ExpenseAccountService $service){ $count=$service->syncFromChartAccounts(BusinessScope::businessId()); return back()->with('status', $count.' expense account(s) synced into Expenses-New'); }
    public function data(){
        $rows=ExpenseAccount::where('business_id',BusinessScope::businessId())->orderBy('name')->get();
        return response()->json(['data'=>$rows->map(fn($a)=>['id'=>$a->id,'name'=>$a->name,'code'=>$a->code,'active'=>$a->is_active?'Yes':'No','action'=>view('expensesnew::accounts.partials.actions',compact('a'))->render()])]);
    }
}
