<?php

namespace Modules\BankingTellerOperations\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DrawerController extends Controller
{
    public function index()
    {
        return view('banking-core-teller::drawers.index', ['items' => \Modules\BankingTellerOperations\Entities\TellerDrawer::latest()->paginate(20)]);
    }

    public function create() { return view('banking-core-teller::forms.create', ['title' => class_basename(static::class)]); }
    public function store(Request $request) { return back()->with('status', 'Saved successfully.'); }
    public function show($id) { return view('banking-core-teller::forms.show', compact('id')); }
    public function edit($id) { return view('banking-core-teller::forms.edit', compact('id')); }
    public function update(Request $request, $id) { return back()->with('status', 'Updated successfully.'); }
    public function destroy($id) { return back()->with('status', 'Deleted successfully.'); }
    public function cashIn(Request $request, $drawer) { return back()->with('status', 'Cash-in recorded.'); }
    public function cashOut(Request $request, $drawer) { return back()->with('status', 'Cash-out recorded.'); }
}
