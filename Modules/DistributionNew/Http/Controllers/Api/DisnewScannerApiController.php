<?php
namespace Modules\DistributionNew\Http\Controllers\Api;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Scanner\DisnewScannerService;
use Modules\DistributionNew\Services\Scanner\DisnewBarcodeService;

class DisnewScannerApiController extends Controller
{
    public function scan(Request $request, DisnewScannerService $scanner, DisnewBarcodeService $barcode)
    {
        $label = $barcode->resolve($request->barcode_value);
        $scan = $scanner->recordScan($request->all()+['resolved_label_id'=>optional($label)->id,'scan_status'=>$label?'accepted':'exception']);
        return response()->json(['success'=>true,'data'=>compact('scan','label')]);
    }
}
