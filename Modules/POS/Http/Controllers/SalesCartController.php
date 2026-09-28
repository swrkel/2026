<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCartService;

class SalesCartController extends Controller
{
    public function __construct(private POSCartService $cart) {}

    public function show(Request $request)
    {
        return response()->json($this->cart->getActiveCart($request));
    }

    public function addLine(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required',
            'quantity' => 'nullable|numeric',
            'unit_price' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        return response()->json($this->cart->addLine($request, $data));
    }

    public function updateLine(Request $request, $line)
    {
        $data = $request->validate([
            'quantity' => 'nullable|numeric',
            'unit_price' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        return response()->json($this->cart->updateLine($request, (int) $line, $data));
    }

    public function removeLine(Request $request, $line)
    {
        return response()->json($this->cart->removeLine($request, (int) $line));
    }

    public function clear(Request $request)
    {
        return response()->json($this->cart->clear($request));
    }

    public function setCustomer(Request $request)
    {
        $data = $request->validate(['customer_id' => 'nullable', 'customer_name' => 'nullable|string|max:191']);
        return response()->json($this->cart->setCustomer($request, $data));
    }

    public function hold(Request $request)
    {
        return response()->json($this->cart->hold($request, $request->input('note')));
    }

    public function suspend(Request $request)
    {
        return response()->json($this->cart->suspend($request, $request->input('note')));
    }

    public function resume(Request $request, $cart)
    {
        return response()->json($this->cart->resume($request, (int) $cart));
    }
}
