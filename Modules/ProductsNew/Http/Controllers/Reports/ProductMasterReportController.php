<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\ProductMasterReportService;
class ProductMasterReportController extends Controller { public function __construct(protected ProductMasterReportService $service) {} public function index(Request $request){ $rows=$this->service->data($request->all()); return view('productsnew::reports.product_master',compact('rows')); } }
