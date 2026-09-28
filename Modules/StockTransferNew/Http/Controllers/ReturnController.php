<?php
namespace Modules\StockTransferNew\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\StockTransferNew\Entities\StockTransfer; use Modules\StockTransferNew\Services\StockTransferReturnService;
class ReturnController extends Controller { public function index(StockTransferReturnService $service){ return view('stocktransfernew::returns.index',['transfers'=>$service->listReceivableReturns()]); } public function store(Request $request, StockTransfer $transfer, StockTransferReturnService $service){ $service->createReturn($transfer,(array)$request->input('return_qty',[]),$request->input('reason')); return redirect()->back()->with('status','Return request created.'); } }
