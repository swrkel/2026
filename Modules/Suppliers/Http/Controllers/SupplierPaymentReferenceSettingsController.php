<?php

namespace Modules\Suppliers\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Suppliers\Services\SupplierPaymentReferenceProfileService;

class SupplierPaymentReferenceSettingsController extends Controller
{
    public function index(Request $request, SupplierPaymentReferenceProfileService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        if (! $service->available()) {
            return view('suppliers::settings.payment_references', [
                'rows' => [], 'contexts' => $service->contexts(), 'editing' => null, 'tableAvailable' => false,
            ]);
        }
        return view('suppliers::settings.payment_references', [
            'rows' => $service->rows($businessId), 'contexts' => $service->contexts(), 'editing' => null, 'tableAvailable' => true,
        ]);
    }

    public function store(Request $request, SupplierPaymentReferenceProfileService $service)
    {
        $this->validateInput($request);
        $businessId = (int) $request->session()->get('user.business_id');
        $service->store($businessId, $request->only(['context_key', 'prefix', 'starting_number']), (int) auth()->id());
        return redirect()->route('suppliers.settings.payment_references.index')->with('status', ['success' => 1, 'msg' => 'Supplier payment prefix added successfully.']);
    }

    public function edit(Request $request, int $id, SupplierPaymentReferenceProfileService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $rows = $service->rows($businessId);
        $editing = collect($rows)->firstWhere('id', $id);
        abort_if(! $editing, 404);
        return view('suppliers::settings.payment_references', [
            'rows' => $rows, 'contexts' => $service->contexts(), 'editing' => $editing, 'tableAvailable' => $service->available(),
        ]);
    }

    public function update(Request $request, int $id, SupplierPaymentReferenceProfileService $service)
    {
        $this->validateInput($request, false);
        $businessId = (int) $request->session()->get('user.business_id');
        $service->update($businessId, $id, $request->only(['prefix', 'starting_number']), (int) auth()->id());
        return redirect()->route('suppliers.settings.payment_references.index')->with('status', ['success' => 1, 'msg' => 'Supplier payment prefix updated successfully.']);
    }

    public function destroy(Request $request, int $id, SupplierPaymentReferenceProfileService $service)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $service->delete($businessId, $id);
        return redirect()->route('suppliers.settings.payment_references.index')->with('status', ['success' => 1, 'msg' => 'Supplier payment prefix deleted successfully.']);
    }

    private function validateInput(Request $request, bool $withContext = true): void
    {
        $rules = [
            'prefix' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\\-\\/]+$/'],
            'starting_number' => ['required', 'integer', 'min:1', 'max:999999999'],
        ];
        if ($withContext) {
            $rules['context_key'] = ['required', 'string', 'max:80'];
        }
        $request->validate($rules);
    }
}
