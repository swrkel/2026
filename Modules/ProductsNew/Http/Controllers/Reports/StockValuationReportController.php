<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Report\StockValuationReportService;
class StockValuationReportController extends Controller { public function __construct(protected StockValuationReportService $service) {} public function index(Request $request){ $rows=$this->service->data($request->all()); return view('productsnew::reports.stock_valuation',compact('rows')); } }
