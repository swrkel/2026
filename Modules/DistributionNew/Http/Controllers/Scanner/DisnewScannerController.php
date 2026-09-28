<?php
namespace Modules\DistributionNew\Http\Controllers\Scanner;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Scanner\DisnewScannerService;
use Modules\DistributionNew\Services\Scanner\DisnewBarcodeService;

class DisnewScannerController extends Controller
{
    public function index(){ return view('distributionnew::scanner.index'); }
    public function loading(){ return view('distributionnew::scanner.loading'); }
    public function unloading(){ return view('distributionnew::scanner.unloading'); }
    public function bins(){ return view('distributionnew::scanner.bins'); }
    public function verify(){ return view('distributionnew::scanner.verify'); }

    public function scan(Request $request, DisnewScannerService $scanner, DisnewBarcodeService $barcode)
    {
        $label = $barcode->resolve($request->input('barcode_value'));
        $scan = $scanner->recordScan($request->all()+[
            'resolved_label_id' => optional($label)->id,
            'product_id' => optional($label)->product_id,
            'scan_status' => $label ? 'accepted' : 'exception'
        ]);
        return response()->json(['success'=>true,'scan'=>$scan,'label'=>$label]);
    }
}
