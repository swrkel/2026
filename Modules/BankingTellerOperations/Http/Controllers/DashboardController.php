<?php

namespace Modules\BankingTellerOperations\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('banking-core-teller::dashboard.index', ['summary' => app(\Modules\BankingTellerOperations\Services\ReportService::class)->summary()]);
    }

    public function create() { return view('banking-core-teller::forms.create', ['title' => class_basename(static::class)]); }
    public function store(Request $request) { return back()->with('status', 'Saved successfully.'); }
    public function show($id) { return view('banking-core-teller::forms.show', compact('id')); }
    public function edit($id) { return view('banking-core-teller::forms.edit', compact('id')); }
    public function update(Request $request, $id) { return back()->with('status', 'Updated successfully.'); }
    public function destroy($id) { return back()->with('status', 'Deleted successfully.'); }
}
