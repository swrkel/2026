<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;

class OperationsReportController extends Controller
{
    public function index() { return view('beautysaloons::'.strtolower(str_replace('Controller','','OperationsReportController')).'.index'); }
    public function create() { return view('beautysaloons::'.strtolower(str_replace('Controller','','OperationsReportController')).'.create'); }
    public function store() { return back()->with('status', ['success' => 1, 'msg' => __('beautysaloons::beautysaloons.saved_successfully')]); }
    public function show($id) { return view('beautysaloons::'.strtolower(str_replace('Controller','','OperationsReportController')).'.show', compact('id')); }
    public function edit($id) { return view('beautysaloons::'.strtolower(str_replace('Controller','','OperationsReportController')).'.edit', compact('id')); }
    public function update($id) { return back()->with('status', ['success' => 1, 'msg' => __('beautysaloons::beautysaloons.updated_successfully')]); }
    public function destroy($id) { return response()->json(['success' => true]); }
    public function dailyCollection() { return view('beautysaloons::reports.daily_collection'); }
    public function appointments() { return view('beautysaloons::reports.appointments'); }
    public function serviceSales() { return view('beautysaloons::reports.service_sales'); }
    public function staffCommissions() { return view('beautysaloons::reports.staff_commissions'); }
}
