<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\CollectionSummaryService;

/**
 * Collection Summary - tab 8 of SW Operators.
 *
 * One shift at a time: operators down, payment types across, totals both ways.
 *
 * CLOSED shifts are included, unlike Daily Cash Status. A summary is something
 * people look back at, and hiding a shift the moment it closes would make it
 * useless the day after.
 */
class CollectionSummaryController extends Controller
{
    public function __construct(protected CollectionSummaryService $summary)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    /** Shifts at a location - every status, most recent first. */
    public function shifts(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = (int) $request->input('location_id');

        if ($businessId <= 0 || $locationId <= 0) {
            return response()->json([]);
        }

        $rows = Shift::where('business_id', $businessId)
            ->where('location_id', $locationId)
            ->orderByDesc('shift_date')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'sw_shift_no', 'shift_date', 'shift_name', 'status', 'closed_at']);

        return response()->json($rows->map(function ($r) {
            $label = $r->sw_shift_no;

            if ($r->shift_date) {
                $label .= '  ·  ' . \Carbon\Carbon::parse($r->shift_date)->format('d/m/Y');
            }

            if (! empty($r->shift_name)) {
                $label .= '  ·  ' . $r->shift_name;
            }

            // The status is on the label, so it is clear whether a shift is
            // still being worked without opening it.
            if (! $r->isOpen()) {
                $label .= '  ·  ' . $r->statusLabel();
            }

            return ['id' => $r->id, 'label' => $label];
        }));
    }

    public function show(Request $request)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)->find((int) $request->input('sw_shift_id'));

        if (! $shift) {
            return response('<div class="alert alert-warning">'
                . __('sw::lang.choose_a_shift') . '</div>');
        }

        return view('sw::operators.partials.collection_summary_panel', [
            'shift' => $shift,
            'summary' => $this->summary->summary((int) $shift->id),
            'types' => CollectionSummaryService::TYPES,
        ]);
    }
}
