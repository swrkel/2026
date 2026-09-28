<?php
namespace Modules\ProductsNew\Http\Controllers\Reports;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\StockCenterService;
class LowStockReportController extends Controller { public function __construct(protected StockCenterService $stock) {} public function index(Request $request){ $filters=$request->all(); $filters['stock_status']='low'; $rows=$this->stock->query($filters)->paginate(50); return view('productsnew::reports.low_stock',compact('rows')); } }
