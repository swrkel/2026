<?php
namespace Modules\StockTransferNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\StockTransferNew\Services\StockTransferExportService;
use Modules\StockTransferNew\Services\StockTransferReportService;

class ReportController extends Controller
{
    public function __construct(protected StockTransferReportService $reports, protected StockTransferExportService $exports) {}
    public function register(Request $request){$transfers=$this->reports->register($request->all());return view('stocktransfernew::reports.transfer_register',compact('transfers'));}
    public function movement(Request $request){$movements=$this->reports->movement($request->all());return view('stocktransfernew::reports.stock_movement',compact('movements'));}
    public function balances(Request $request){$balances=$this->reports->balances($request->all());return view('stocktransfernew::reports.balances',compact('balances'));}
    public function inTransit(Request $request){$transfers=$this->reports->inTransit($request->all());return view('stocktransfernew::reports.stock_in_transit',compact('transfers'));}
    public function variance(Request $request){$rows=$this->reports->variance($request->all());return view('stocktransfernew::reports.variance',compact('rows'));}
    public function aging(Request $request){$transfers=$this->reports->aging($request->all());return view('stocktransfernew::reports.aging',compact('transfers'));}
    public function export(Request $request,string $type){$headers=$type==='aging'?['Transfer No','Transfer Date','Status','From Location','To Location','Age Days']:['Transfer No','Transfer Date','Status','Product ID','Variation ID','Requested','Dispatched','Received','Short','Excess','Remarks'];return $this->exports->csv('stock-transfer-new-'.$type.'-'.date('YmdHis').'.csv',$headers,$this->reports->exportRows($type,$request->all()));}
}
