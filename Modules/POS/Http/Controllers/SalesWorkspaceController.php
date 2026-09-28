<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSSalesWorkspaceService;
use Modules\POS\Services\POSCartService;

class SalesWorkspaceController extends Controller
{
    public function __construct(
        private POSSalesWorkspaceService $workspace,
        private POSCartService $cart
    ) {}

    public function index(Request $request)
    {
        $context = $this->workspace->getContext($request);
        $cart = $this->cart->getActiveCart($request);

        return view('pos::sales.workspace', [
            'title' => __('pos::page_003.sales_workspace'),
            'context' => $context,
            'cart' => $cart,
            'quick_actions' => $this->workspace->quickActions(),
            'product_filters' => $this->workspace->productFilters($request),
            'payment_methods' => $this->workspace->paymentMethods($request),
        ]);
    }

    public function products(Request $request)
    {
        return response()->json($this->workspace->searchProducts($request));
    }

    public function customers(Request $request)
    {
        return response()->json($this->workspace->searchCustomers($request));
    }

    public function priceCheck(Request $request)
    {
        return response()->json($this->workspace->priceCheck($request));
    }

    public function calculator()
    {
        return view('pos::sales.calculator', [
            'title' => __('pos::page_003.calculator'),
        ]);
    }
}
