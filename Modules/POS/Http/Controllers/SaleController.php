<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCartService;
use Modules\POS\Services\POSSaleCheckoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Modules\POS\Services\POSCustomersModuleBridgeService;

class SaleController extends Controller
{
    public function __construct(private POSCartService $cart, private POSSaleCheckoutService $checkout, private POSCustomersModuleBridgeService $customersBridge) {}

    public function index(Request $request)
    {
        $title = 'POS Sales';
        $cart = $this->cart->getActiveCart($request);
        $products = $this->cart->searchProducts($request->input('q'), 24);
        $held = $this->cart->heldSales($request);
        $customers = collect($this->customersBridge->search($request, 200)['data'] ?? []);
        return view('pos::sales.index', compact('title', 'cart', 'products', 'held', 'customers'));
    }

    public function searchProducts(Request $request)
    {
        $term = trim((string) $request->input('q', ''));
        if (strlen($term) > 764) {
            // 191 utf8mb4 characters can occupy up to 764 bytes. Keep the
            // request bounded without depending on the mbstring extension.
            $term = substr($term, 0, 764);
        }

        try {
            return response()->json([
                'success' => true,
                'products' => $this->cart->searchProducts($term, 30),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('POS product search failed', [
                'business_id' => $request->session()->get('business.id') ?? $request->session()->get('user.business_id'),
                'user_id' => auth()->id(),
                'term' => $term,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'products' => [],
                'message' => 'Unable to load products. Please retry.',
            ], 500);
        }
    }

    public function barcode(Request $request)
    {
        $request->validate(['barcode' => 'required|string|max:191']);
        return response()->json($this->cart->addByBarcode($request, $request->input('barcode')));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'payment_method' => 'required|string|max:50',
            'paid_amount' => 'required|numeric|min:0',
            'reference_no' => 'nullable|string|max:191',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
        ]);
        $result = $this->checkout->checkout($request, $data);
        if ($request->ajax()) {
            return response()->json($result);
        }
        if (!($result['success'] ?? false)) {
            return back()->with('warning', $result['message'] ?? 'Unable to complete sale.');
        }
        return redirect()->route('pos.sales.receipt', $result['sale_id'])->with('status', 'Sale completed successfully.');
    }

    public function receipt($sale)
    {
        $title = 'POS Receipt';
        $receipt = $this->checkout->receipt((int) $sale);
        abort_if(!$receipt, 404);
        return view('pos::sales.receipt', compact('title', 'receipt'));
    }

    public function list(Request $request)
    {
        $title = 'POS Sales List';
        $sales = $this->checkout->salesList($request);
        return view('pos::sales.list', compact('title', 'sales'));
    }
}
