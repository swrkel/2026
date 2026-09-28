<?php

namespace Modules\StockTransferNew\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTransferNew\Services\Admin\TransferReservationControlService;

class TransferReservationControlController extends Controller
{
    public function __construct(private TransferReservationControlService $service)
    {
        $this->middleware('permission:stock_transfer_new.reservations.view')->only(['index', 'candidates', 'export']);
        $this->middleware('permission:stock_transfer_new.reservations.create')->only(['reserve']);
        $this->middleware('permission:stock_transfer_new.reservations.release')->only(['release', 'expire']);
        $this->middleware('permission:stock_transfer_new.reservations.export')->only(['export']);
    }

    public function index(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'reservation_status', 'from_business_id', 'from_location_id', 'from_store_id', 'product_id']);
        return view('stocktransfernew::admin.reservations.index', [
            'summary' => $this->service->summary($filters),
            'rows' => $this->service->rows($filters),
            'filters' => $filters,
        ]);
    }

    public function candidates(Request $request)
    {
        return view('stocktransfernew::admin.reservations.candidates', [
            'rows' => $this->service->candidates($request->all()),
            'filters' => $request->all(),
        ]);
    }

    public function reserve(Request $request, int $transferLineId)
    {
        $request->validate(['expiry_hours' => 'nullable|integer|min:1|max:720']);
        $this->service->reserveCandidate($transferLineId, (int) auth()->id(), $request->integer('expiry_hours') ?: null);
        return back()->with('status', __('stocktransfernew::reservations.reserved_successfully'));
    }

    public function release(Request $request, int $reservationId)
    {
        $request->validate(['release_reason' => 'required|string|max:1000']);
        $this->service->release($reservationId, $request->input('release_reason'), (int) auth()->id());
        return back()->with('status', __('stocktransfernew::reservations.released_successfully'));
    }

    public function expire()
    {
        $count = $this->service->expireOverdue((int) auth()->id());
        return back()->with('status', trans('stocktransfernew::reservations.expired_count', ['count' => $count]));
    }

    public function export(Request $request)
    {
        $csv = $this->service->exportCsv($request->all());
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="stock_transfer_new_reservations.csv"',
        ]);
    }
}
