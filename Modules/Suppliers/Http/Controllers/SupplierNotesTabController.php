<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Modules\Suppliers\Entities\Supplier;
use Modules\Suppliers\Services\Notes\SupplierNoteService;
use Modules\Suppliers\Utils\SupplierProfileAccessUtil;

class SupplierNotesTabController extends Controller
{
    public function index(Supplier $supplier, SupplierNoteService $noteService)
    {
        SupplierProfileAccessUtil::ensureTenantSupplier($supplier);

        return view('suppliers::suppliers.profile.tabs.notes', [
            'supplier' => $supplier,
            'notes' => $noteService->latest($supplier),
            'standaloneTab' => true,
        ]);
    }
}
