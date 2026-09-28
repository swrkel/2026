<?php
namespace Modules\Suppliers\Http\Controllers;
use Modules\Suppliers\Http\Controllers\SuppliersBaseController as Controller; use Illuminate\Http\Request; use Modules\Suppliers\Services\SupplierQueryService;
class SupplierReportController extends Controller { public function __construct(private SupplierQueryService $supplierQueryService){} public function index(Request $request){ $suppliers = $this->supplierQueryService->listQuery($request->only(['search','location_id']))->paginate($request->integer('per_page',25)); return view('suppliers::reports.index', compact('suppliers')); } }
