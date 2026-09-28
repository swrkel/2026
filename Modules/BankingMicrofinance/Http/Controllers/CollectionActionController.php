<?php
namespace Modules\BankingMicrofinance\Http\Controllers;
use Illuminate\Routing\Controller; use Illuminate\Http\Request;
class CollectionActionController extends Controller {
 public function index(Request $request) { return view('bankingmicrofinance::collectionaction.index'); }
 public function create() { return view('bankingmicrofinance::collectionaction.create'); }
 public function store(Request $request) { return redirect()->back()->with('status','Saved successfully'); }
 public function show($id) { return view('bankingmicrofinance::collectionaction.show', compact('id')); }
 public function edit($id) { return view('bankingmicrofinance::collectionaction.edit', compact('id')); }
 public function update(Request $request, $id) { return redirect()->back()->with('status','Updated successfully'); }
}
