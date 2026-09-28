<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierNoteService;

class SupplierNoteController extends Controller
{
    protected SupplierNoteService $service;

    public function __construct(SupplierNoteService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $notes = $this->service->list($supplier);

        return view('suppliers::communication.notes.index', compact('supplier', 'notes'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $this->service->store($supplier, $request->all());

        return redirect()->route('suppliers.communication.notes.index', $supplier)
            ->with('status', __('suppliers::lang.note_saved'));
    }
}
