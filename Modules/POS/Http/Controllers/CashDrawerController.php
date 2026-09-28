<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSCashDrawerService;
use Modules\POS\Services\POSShiftService;

class CashDrawerController extends Controller
{
    public function __construct(private POSCashDrawerService $drawer, private POSShiftService $shifts) {}

    public function index()
    {
        return view('pos::cash_drawer.index', [
            'title' => __('pos::messages.cash_drawer'),
            'movements' => $this->drawer->movements(),
            'dashboard' => $this->drawer->dashboard(),
        ]);
    }

    public function cashInForm()
    {
        return view('pos::cash_drawer.cash_in', ['title' => __('pos::messages.cash_in'), 'sessions' => $this->drawer->openSessions()]);
    }

    public function cashIn(Request $request)
    {
        $request->validate(['register_session_id' => 'required|integer', 'amount' => 'required|numeric|min:0.01', 'transaction_date' => 'nullable|date', 'reference_no' => 'nullable|string|max:191', 'note' => 'nullable|string']);
        $id = $this->drawer->createMovement('cash_in', $request->all());
        return redirect()->route('pos.cash_drawer.index')->with($id ? 'status' : 'warning', $id ? __('pos::messages.saved_successfully') : __('pos::messages.table_not_ready'));
    }

    public function cashOutForm()
    {
        return view('pos::cash_drawer.cash_out', ['title' => __('pos::messages.cash_out'), 'sessions' => $this->drawer->openSessions()]);
    }

    public function cashOut(Request $request)
    {
        $request->validate(['register_session_id' => 'required|integer', 'amount' => 'required|numeric|min:0.01', 'transaction_date' => 'nullable|date', 'reference_no' => 'nullable|string|max:191', 'note' => 'nullable|string']);
        $id = $this->drawer->createMovement('cash_out', $request->all());
        return redirect()->route('pos.cash_drawer.index')->with($id ? 'status' : 'warning', $id ? __('pos::messages.saved_successfully') : __('pos::messages.table_not_ready'));
    }

    public function countForm()
    {
        return view('pos::cash_drawer.count', ['title' => __('pos::messages.cash_count'), 'sessions' => $this->drawer->openSessions()]);
    }

    public function count(Request $request)
    {
        $request->validate(['register_session_id' => 'required|integer', 'expected_amount' => 'nullable|numeric', 'actual_amount' => 'required|numeric', 'counted_at' => 'nullable|date', 'note' => 'nullable|string']);
        $id = $this->drawer->createCount($request->all());
        return redirect()->route('pos.cash_drawer.index')->with($id ? 'status' : 'warning', $id ? __('pos::messages.saved_successfully') : __('pos::messages.table_not_ready'));
    }
}
