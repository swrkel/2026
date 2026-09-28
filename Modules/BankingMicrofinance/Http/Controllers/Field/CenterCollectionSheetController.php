<?php

namespace Modules\BankingMicrofinance\Http\Controllers\Field;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CenterCollectionSheetController extends Controller
{
    public function index() { return view('bankingmicrofinance::field.collection_sheets.index', ['rows' => \Modules\BankingMicrofinance\Entities\CenterCollectionSheet::latest()->paginate(20)]); }
    public function create() { return view('bankingmicrofinance::field.forms.generic'); }
    public function store(Request $request) { return back()->with('status', 'Saved successfully.'); }
    public function edit($id) { return view('bankingmicrofinance::field.forms.generic', compact('id')); }
    public function update(Request $request, $id) { return back()->with('status', 'Updated successfully.'); }
    public function destroy($id) { return back()->with('status', 'Deleted successfully.'); }
}
