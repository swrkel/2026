<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyCustomerProfile;
use Modules\BeautySaloons\Services\BeautyCustomerService;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $customers = BeautyCustomerProfile::query()->latest('id');
            return datatables()->of($customers)
                ->addColumn('action', fn ($row) => view('beautysaloons::customers.partials.actions', compact('row'))->render())
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('beautysaloons::customers.index');
    }

    public function create()
    {
        return view('beautysaloons::customers.create');
    }

    public function store(Request $request, BeautyCustomerService $service)
    {
        $service->create($request->all());
        return redirect()->route('beautysaloons.customers.index')->with('status', ['success' => 1, 'msg' => __('beautysaloons::customers.customer_added_successfully')]);
    }

    public function edit($id)
    {
        $customer = BeautyCustomerProfile::findOrFail($id);
        return view('beautysaloons::customers.edit', compact('customer'));
    }

    public function update(Request $request, $id, BeautyCustomerService $service)
    {
        $customer = BeautyCustomerProfile::findOrFail($id);
        $service->update($customer, $request->all());
        return redirect()->route('beautysaloons.customers.index')->with('status', ['success' => 1, 'msg' => __('beautysaloons::customers.customer_updated_successfully')]);
    }

    public function show($id)
    {
        $customer = BeautyCustomerProfile::findOrFail($id);
        return view('beautysaloons::customers.show', compact('customer'));
    }
}
