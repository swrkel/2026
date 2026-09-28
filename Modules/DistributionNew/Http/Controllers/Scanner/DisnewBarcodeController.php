<?php
namespace Modules\DistributionNew\Http\Controllers\Scanner;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Services\Scanner\DisnewBarcodeService;

class DisnewBarcodeController extends Controller
{
    public function labels(){ return view('distributionnew::scanner.labels'); }
    public function store(Request $request, DisnewBarcodeService $service)
    {
        $label = $service->createLabel($request->all()+['created_by'=>auth()->id()]);
        return response()->json(['success'=>true,'label'=>$label]);
    }
}
