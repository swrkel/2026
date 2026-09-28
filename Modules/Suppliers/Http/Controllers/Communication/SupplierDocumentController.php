<?php

namespace Modules\Suppliers\Http\Controllers\Communication;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Communication\SupplierDocumentService;

class SupplierDocumentController extends Controller
{
    protected SupplierDocumentService $service;

    public function __construct(SupplierDocumentService $service)
    {
        $this->service = $service;
    }

    public function index(Supplier $supplier)
    {
        $documents = $this->service->list($supplier);

        return view('suppliers::communication.documents.index', compact('supplier', 'documents'));
    }

    public function store(Request $request, Supplier $supplier)
    {
        $this->service->store($supplier, $request);

        return redirect()->route('suppliers.communication.documents.index', $supplier)
            ->with('status', __('suppliers::lang.document_uploaded'));
    }

    public function destroy(Supplier $supplier, int $document)
    {
        $this->service->delete($supplier, $document);

        return redirect()->route('suppliers.communication.documents.index', $supplier)
            ->with('status', __('suppliers::lang.document_deleted'));
    }
}
