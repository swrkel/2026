<?php

namespace Modules\Suppliers\Http\Controllers;

use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller;
use Illuminate\Http\Request;
use Modules\Suppliers\Services\Exports\SupplierExportService;

class SupplierExportController extends Controller
{
    public function __construct(private SupplierExportService $supplierExportService)
    {
        $this->middleware('auth');
    }

    public function __invoke(Request $request, string $format)
    {
        $filters = $request->only(['search', 'location_id', 'date_range']);

        return $this->supplierExportService->export($format, $filters);
    }
}
