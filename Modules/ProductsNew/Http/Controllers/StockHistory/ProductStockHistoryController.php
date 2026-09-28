<?php

namespace Modules\ProductsNew\Http\Controllers\StockHistory;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\StockHistory\ProductStockHistoryService;

class ProductStockHistoryController extends Controller
{
    public function __construct(protected ProductStockHistoryService $service)
    {
    }

    public function index(Request $request)
    {
        return view('productsnew::stock_history.index', $this->service->report($request->all()) + $this->service->filterOptions($request->all()));
    }
}
