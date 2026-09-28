<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewTripExpense;

class TripExpenseController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewTripExpense::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::trip_expenses.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::trip_expenses.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewTripExpense::create($data);
        return redirect()->route('distributionnew.trip_expenses.index')->with('status', __('distributionnew::lang.trip_expense_created'));
    }

    public function edit($id)
    {
        $record = DisnewTripExpense::findOrFail($id);
        return view('distributionnew::trip_expenses.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewTripExpense::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.trip_expenses.index')->with('status', __('distributionnew::lang.trip_expense_updated'));
    }
}
