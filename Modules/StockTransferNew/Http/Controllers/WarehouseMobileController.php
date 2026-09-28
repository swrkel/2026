<?php
namespace Modules\StockTransferNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\ScanSession;
use Modules\StockTransferNew\Services\WarehouseScanService;

class WarehouseMobileController extends Controller
{
    protected WarehouseScanService $scanService;

    public function __construct(WarehouseScanService $scanService)
    {
        $this->scanService = $scanService;
    }

    public function index(Request $request)
    {
        $businessId = (int)$request->session()->get('user.business_id');
        $openSessions = ScanSession::where('business_id', $businessId)->where('status', 'open')->latest()->limit(20)->get();
        return view('stocktransfernew::warehouse.index', compact('openSessions'));
    }

    public function dispatch(Request $request)
    {
        return view('stocktransfernew::warehouse.dispatch');
    }

    public function receive(Request $request)
    {
        return view('stocktransfernew::warehouse.receive');
    }

    public function openSession(Request $request)
    {
        $businessId = (int)$request->session()->get('user.business_id');
        $session = $this->scanService->openSession([
            'business_id' => $businessId,
            'transfer_id' => $request->input('transfer_id'),
            'scan_type' => $request->input('scan_type', 'dispatch'),
            'location_id' => $request->input('location_id'),
            'store_id' => $request->input('store_id'),
            'device_code' => $request->input('device_code'),
        ]);
        return redirect()->route('stock-transfer-new.warehouse.session', $session->id);
    }

    public function session(Request $request, ScanSession $session)
    {
        $this->authorizeBusiness($request, $session);
        $summary = $this->scanService->summary($session);
        $lines = $session->lines()->latest()->paginate(50);
        return view('stocktransfernew::warehouse.session', compact('session', 'summary', 'lines'));
    }

    public function scan(Request $request, ScanSession $session)
    {
        $this->authorizeBusiness($request, $session);
        $request->validate(['barcode' => 'required|string|max:191', 'qty' => 'nullable|numeric|min:0.0001']);
        $line = $this->scanService->recordScan($session, $request->all());
        if ($request->ajax()) {
            return response()->json(['success' => true, 'line' => $line, 'summary' => $this->scanService->summary($session)]);
        }
        return back()->with('status', __('stocktransfernew::lang.scan_saved'));
    }

    public function submit(Request $request, ScanSession $session)
    {
        $this->authorizeBusiness($request, $session);
        $this->scanService->submit($session, $request->input('remarks'));
        return redirect()->route('stock-transfer-new.warehouse.index')->with('status', __('stocktransfernew::lang.scan_session_submitted'));
    }

    protected function authorizeBusiness(Request $request, ScanSession $session): void
    {
        abort_unless((int)$session->business_id === (int)$request->session()->get('user.business_id'), 403);
    }
}
