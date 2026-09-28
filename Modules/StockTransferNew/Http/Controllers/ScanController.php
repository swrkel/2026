<?php
namespace Modules\StockTransferNew\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\StockTransferNew\Services\StockTransferBarcodeService;
class ScanController extends Controller { public function index(){ return view('stocktransfernew::scan.index'); } public function addLine(Request $request, StockTransferBarcodeService $barcodes){ $request->validate(['barcode'=>'required|string|max:191']); try { return response()->json(['success'=>true,'line'=>$barcodes->lineFromBarcode($request->barcode)]); } catch(\Throwable $e){ return response()->json(['success'=>false,'message'=>$e->getMessage()],422); } } }
