<?php
namespace Modules\BankingMicrofinance\Http\Controllers;
use Illuminate\Routing\Controller; use Illuminate\Http\Request;
class RepossessionController extends Controller {
 public function index(Request $request) { return view('bankingmicrofinance::repossession.index'); }
 public function create() { return view('bankingmicrofinance::repossession.create'); }
 public function store(Request $request) { return redirect()->back()->with('status','Saved successfully'); }
 public function show($id) { return view('bankingmicrofinance::repossession.show', compact('id')); }
 public function edit($id) { return view('bankingmicrofinance::repossession.edit', compact('id')); }
 public function update(Request $request, $id) { return redirect()->back()->with('status','Updated successfully'); }
}
