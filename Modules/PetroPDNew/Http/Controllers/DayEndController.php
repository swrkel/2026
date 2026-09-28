<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Http\Requests\DayEndStoreRequest;
use Modules\PetroPDNew\Http\Requests\WorkflowRequest;
use Modules\PetroPDNew\Services\DayEnd\PdnewDayEndService;
use Modules\PetroPDNew\Services\PdnewMasterDataService;

class DayEndController extends PdnewController
{
    public function index(Request $request)
    {
        $query = \Modules\PetroPDNew\Entities\PdnewDayEnd::query()
            ->forBusiness($this->context->businessId());

        if ($this->context->locationId()) $query->where('location_id', $this->context->locationId());
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date_from')) $query->whereDate('day_end_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $query->whereDate('day_end_date', '<=', $request->date_to);

        $dayEnds = $query->orderByDesc('day_end_date')->orderByDesc('id')->paginate(50)->withQueryString();
        return view('petropdnew::day-ends.index', compact('dayEnds'));
    }

    public function create(PdnewMasterDataService $masterData)
    {
        $locations = $masterData->locations($this->context->businessId());
        $currentLocationId = $this->context->locationId();

        return view('petropdnew::day-ends.create', compact('locations', 'currentLocationId'));
    }

    public function store(DayEndStoreRequest $request, PdnewDayEndService $service)
    {
        try {
            $locationId = (int) ($request->validated('location_id') ?: $this->context->locationId()) ?: null;
            $this->context->authorizeLocation($locationId);

            $dayEnd = $service->prepare(
                $this->context->businessId(),
                $locationId,
                (string) $request->validated('day_end_date'),
                $this->context->userId(),
                $request->validated('note')
            );

            return redirect()->route('petro-pd-new.day-ends.show', $dayEnd->id)
                ->with('success', 'Petro PD-New Day End prepared.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function show(int $dayEnd)
    {
        $model = $this->dayEnd($dayEnd)->load('settlements');
        return view('petropdnew::day-ends.show', ['dayEnd' => $model]);
    }

    public function refresh(int $dayEnd, PdnewDayEndService $service)
    {
        try {
            $service->refresh($this->dayEnd($dayEnd));
            return back()->with('success', 'Day End refreshed from finalized Petro PD-New settlements.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function finalize(WorkflowRequest $request, int $dayEnd, PdnewDayEndService $service)
    {
        try {
            $service->finalize($this->dayEnd($dayEnd), $this->context->userId(), $request->validated('note'));
            return back()->with('success', 'Day End finalized.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
