<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Customers\Entities\CustomerGroup;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerGroupController extends Controller
{
    protected $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    protected function businessId(): int
    {
        return (int) (request()->session()->get('business.id') ?: request()->session()->get('user.business_id'));
    }

    public function index()
    {
        $this->permissionService->authorize('settings');
        $records = CustomerGroup::where('business_id', $this->businessId())
            ->where(function ($query) {
                $query->where('type', 'customer')->orWhereNull('type');
            })
            ->orderBy('name')
            ->get();
        $title = 'Customer Groups';
        $routePrefix = 'customers.master.groups';
        $key = 'groups';

        return view('customers::master_data.index', compact('records', 'title', 'routePrefix', 'key'));
    }

    public function create()
    {
        $this->permissionService->authorize('settings');
        $record = null;
        $title = 'Add Customer Group';
        $routePrefix = 'customers.master.groups';
        $key = 'groups';

        return view('customers::master_data.form', compact('record', 'title', 'routePrefix', 'key'));
    }

    public function store(Request $request)
    {
        $this->permissionService->authorize('settings');
        $request->validate(['name' => 'required|string|max:191']);

        CustomerGroup::create([
            'business_id' => $this->businessId(),
            'name' => $request->name,
            'amount' => $request->input('amount', 0),
            'price_calculation_type' => $request->input('price_calculation_type', 'percentage'),
            'type' => 'customer',
            'created_by' => optional(auth()->user())->id,
        ]);

        return redirect()->route('customers.master.groups.index')->with('status', ['success' => 1, 'msg' => __('customers::lang.record_saved_successfully')]);
    }

    public function edit($id)
    {
        $this->permissionService->authorize('settings');
        $record = CustomerGroup::where('business_id', $this->businessId())->findOrFail($id);
        $title = 'Edit Customer Group';
        $routePrefix = 'customers.master.groups';
        $key = 'groups';

        return view('customers::master_data.form', compact('record', 'title', 'routePrefix', 'key'));
    }

    public function update(Request $request, $id)
    {
        $this->permissionService->authorize('settings');
        $request->validate(['name' => 'required|string|max:191']);
        $record = CustomerGroup::where('business_id', $this->businessId())->findOrFail($id);
        $record->update([
            'name' => $request->name,
            'amount' => $request->input('amount', $record->amount ?? 0),
            'price_calculation_type' => $request->input('price_calculation_type', $record->price_calculation_type ?? 'percentage'),
            'type' => 'customer',
        ]);

        return redirect()->route('customers.master.groups.index')->with('status', ['success' => 1, 'msg' => __('customers::lang.record_updated_successfully')]);
    }

    public function destroy($id)
    {
        $this->permissionService->authorize('settings');
        CustomerGroup::where('business_id', $this->businessId())->where('id', $id)->delete();

        return redirect()->route('customers.master.groups.index')->with('status', ['success' => 1, 'msg' => __('customers::lang.record_deleted_successfully')]);
    }
}
