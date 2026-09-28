<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\POS\Services\POSRegisterService;
use Modules\POS\Services\POSShiftService;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = collect();
        $dashboard = [
            'open_shifts' => 0,
            'closed_today' => 0,
            'cash_in_today' => 0,
            'cash_out_today' => 0,
        ];
        $pageWarning = null;

        try {
            $service = app(POSShiftService::class);
            $shifts = $service->list();
        } catch (\Throwable $e) {
            $pageWarning = 'Shift records could not be loaded, but the page remains available.';
            Log::error('POS shift list failed', ['exception' => $e]);
        }

        try {
            $service = $service ?? app(POSShiftService::class);
            $dashboard = $service->dashboard();
        } catch (\Throwable $e) {
            $pageWarning = 'Shift totals could not be loaded, but the page remains available.';
            Log::error('POS shift dashboard failed', ['exception' => $e]);
        }

        return view('pos::shifts.index', [
            'title' => __('pos::messages.shifts'),
            'shifts' => $shifts,
            'dashboard' => $dashboard,
            'pageWarning' => $pageWarning,
        ]);
    }

    public function current()
    {
        $shift = app(POSShiftService::class)->current();

        return view('pos::shifts.current', [
            'title' => __('pos::messages.current_shift'),
            'shift' => $shift,
            'summary' => $shift ? app(POSShiftService::class)->summary((int) $shift->id) : null,
        ]);
    }

    public function openForm()
    {
        return view('pos::shifts.open', [
            'title' => __('pos::messages.open_shift'),
            'registers' => app(POSRegisterService::class)->activeRegisters(),
        ]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'register_id' => 'required|integer',
            'opening_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        $id = app(POSShiftService::class)->open($request->all());
        if (! $id) {
            return back()->with('warning', __('pos::messages.shift_already_open_or_table_not_ready'));
        }

        return redirect()
            ->route('pos.shifts.current')
            ->with('status', __('pos::messages.shift_opened_successfully'));
    }

    public function closeForm($session)
    {
        return view('pos::shifts.close', [
            'title' => __('pos::messages.close_shift'),
            'summary' => app(POSShiftService::class)->summary((int) $session),
        ]);
    }

    public function close(Request $request, $session)
    {
        $request->validate([
            'actual_closing_amount' => 'required|numeric',
            'closing_note' => 'nullable|string',
        ]);

        app(POSShiftService::class)->close((int) $session, $request->all());

        return redirect()
            ->route('pos.shifts.index')
            ->with('status', __('pos::messages.shift_closed_successfully'));
    }

    public function summary($session)
    {
        return view('pos::shifts.summary', [
            'title' => __('pos::messages.shift_summary'),
            'summary' => app(POSShiftService::class)->summary((int) $session),
        ]);
    }
}
