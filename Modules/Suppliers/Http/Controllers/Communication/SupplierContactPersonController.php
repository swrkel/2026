<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierContactPersonService;

class SupplierContactPersonController extends Controller
{
    protected SupplierContactPersonService $service;

    public function __construct(SupplierContactPersonService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $contacts = $this->service->list($supplier);

        return view('suppliers::communication.contacts.index', compact('supplier', 'contacts'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $this->service->store($supplier, $request->all());

        return redirect()->route('suppliers.communication.contacts.index', $supplier)
            ->with('status', __('suppliers::lang.contact_person_saved'));
    }

    public function update(Request $request, Supplier $supplier, int $contact)
    {
        $this->service->update($supplier, $contact, $request->all());

        return redirect()->route('suppliers.communication.contacts.index', $supplier)
            ->with('status', __('suppliers::lang.contact_person_updated'));
    }

    public function destroy(Supplier $supplier, int $contact)
    {
        $this->service->delete($supplier, $contact);

        return redirect()->route('suppliers.communication.contacts.index', $supplier)
            ->with('status', __('suppliers::lang.contact_person_deleted'));
    }
}
