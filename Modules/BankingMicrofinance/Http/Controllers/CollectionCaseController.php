<?php
namespace Modules\BankingMicrofinance\Http\Controllers;
use Illuminate\Routing\Controller; use Illuminate\Http\Request;
class CollectionCaseController extends Controller {
 public function index(Request $request) { return view('bankingmicrofinance::collectioncase.index'); }
 public function create() { return view('bankingmicrofinance::collectioncase.create'); }
 public function store(Request $request) { return redirect()->back()->with('status','Saved successfully'); }
 public function show($id) { return view('bankingmicrofinance::collectioncase.show', compact('id')); }
 public function edit($id) { return view('bankingmicrofinance::collectioncase.edit', compact('id')); }
 public function update(Request $request, $id) { return redirect()->back()->with('status','Updated successfully'); }
}
