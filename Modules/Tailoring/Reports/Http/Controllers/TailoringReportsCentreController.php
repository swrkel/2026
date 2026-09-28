<?php
namespace Modules\Tailoring\Reports\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\Tailoring\Reports\Services\TailoringReportCentreService;
class TailoringReportsCentreController extends Controller
{
    protected $service;
    public function __construct(TailoringReportCentreService $service){ $this->service=$service; }
    public function index(Request $request)
    {
        $summary=$this->service->summary($request);
        return view('tailoring::reports.centre', compact('summary'));
    }
}
