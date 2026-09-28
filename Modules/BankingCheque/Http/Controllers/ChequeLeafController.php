<?php
namespace Modules\BankingCheque\Http\Controllers;
use Illuminate\Routing\Controller; use Illuminate\Http\Request; use Modules\BankingCheque\Entities\ChequeLeaf;
class ChequeLeafController extends Controller { public function index(){ $leaves=ChequeLeaf::latest()->paginate(50); return view('bankingcheque::cheque_leaves.index',compact('leaves')); } public function show(ChequeLeaf $leaf){ return view('bankingcheque::cheque_leaves.show',compact('leaf')); } public function update(Request $r, ChequeLeaf $leaf){ $leaf->update($r->only(['status','amount','payee_name','cheque_date','presented_date','reference_no','notes'])); return back()->with('status','Leaf updated'); } }
