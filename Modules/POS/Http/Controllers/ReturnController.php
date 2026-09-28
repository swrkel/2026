<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\POS\Services\POSReturnService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $title = 'POS Returns, Refunds & Exchanges';
        $stats = [
            'returns_count' => 0,
            'refund_total' => 0,
            'pending_approval' => 0,
            'exchanges_count' => 0,
        ];
        $returns = collect();
        $sales = collect();
        $pageWarning = null;

        try {
            $service = app(POSReturnService::class);
            $stats = $service->dashboardStats($request);
        } catch (\Throwable $e) {
            $pageWarning = 'Return totals could not be loaded, but the page remains available.';
            Log::error('POS returns dashboard failed', ['exception' => $e]);
        }

        try {
            $service = $service ?? app(POSReturnService::class);
            $returns = $service->returnsList($request);
        } catch (\Throwable $e) {
            $pageWarning = 'Some return information could not be loaded, but the page remains available.';
            Log::error('POS returns list failed', ['exception' => $e]);
        }

        try {
            $service = $service ?? app(POSReturnService::class);
            $sales = $service->recentSales();
        } catch (\Throwable $e) {
            $pageWarning = 'Some sales information could not be loaded, but the page remains available.';
            Log::error('POS recent sales for returns failed', ['exception' => $e]);
        }

        return view('pos::returns.index', compact(
            'title',
            'stats',
            'returns',
            'sales',
            'pageWarning'
        ));
    }

    public function create(Request $request)
    {
        $title = 'Create POS Return / Refund';
        $sales = app(POSReturnService::class)->recentSales();
        $sale = $request->filled('sale_id')
            ? app(POSReturnService::class)->saleForReturn((int) $request->input('sale_id'))
            : null;

        return view('pos::returns.create', compact('title', 'sales', 'sale'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|integer',
            'return_date' => 'nullable|date',
            'refund_method' => 'required|string|max:50',
            'approval_status' => 'nullable|string|max:30',
            'note' => 'nullable|string|max:1000',
            'lines' => 'required|array',
        ]);

        $result = app(POSReturnService::class)->storeReturn($data);
        if (! ($result['success'] ?? false)) {
            return back()->withInput()->with('warning', $result['message'] ?? 'Return failed.');
        }

        return redirect()
            ->route('pos.returns.receipt', $result['return_id'])
            ->with('status', $result['message']);
    }

    public function receipt($return)
    {
        $title = 'POS Return Receipt';
        $receipt = app(POSReturnService::class)->returnReceipt((int) $return);
        abort_if(! $receipt, 404);

        return view('pos::returns.receipt', compact('title', 'receipt'));
    }

    public function approve(Request $request, $return)
    {
        $request->validate([
            'approval_status' => 'required|string|in:approved,rejected,pending',
            'approval_note' => 'nullable|string|max:1000',
        ]);

        $result = app(POSReturnService::class)->approveReturn(
            (int) $return,
            $request->input('approval_status'),
            $request->input('approval_note')
        );

        return back()->with(
            ($result['success'] ?? false) ? 'status' : 'warning',
            $result['message'] ?? 'Unable to update approval.'
        );
    }

    public function exchanges(Request $request)
    {
        $title = 'POS Exchanges';
        $exchanges = app(POSReturnService::class)->exchangesList($request);
        $sales = app(POSReturnService::class)->recentSales();

        return view('pos::returns.exchanges', compact('title', 'exchanges', 'sales'));
    }

    public function createExchange(Request $request)
    {
        $title = 'Create POS Exchange';
        $sales = app(POSReturnService::class)->recentSales();
        $sale = $request->filled('sale_id')
            ? app(POSReturnService::class)->saleForReturn((int) $request->input('sale_id'))
            : null;
        $products = app(POSReturnService::class)->exchangeProducts();

        return view('pos::returns.exchange_create', compact('title', 'sales', 'sale', 'products'));
    }

    public function storeExchange(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'required|integer',
            'exchange_date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'note' => 'nullable|string|max:1000',
            'return_lines' => 'required|array',
            'new_lines' => 'nullable|array',
        ]);

        $result = app(POSReturnService::class)->storeExchange($data);
        if (! ($result['success'] ?? false)) {
            return back()->withInput()->with('warning', $result['message'] ?? 'Exchange failed.');
        }

        return redirect()->route('pos.exchanges.index')->with('status', $result['message']);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        return app(POSReturnService::class)->exportReturnsCsv($request);
    }

    public function voidSale(Request $request, $sale)
    {
        $request->validate(['reason' => 'nullable|string|max:1000']);
        $result = app(POSReturnService::class)->voidSale((int) $sale, $request->input('reason'));

        return back()->with(
            ($result['success'] ?? false) ? 'status' : 'warning',
            $result['message'] ?? 'Unable to void sale.'
        );
    }
}
