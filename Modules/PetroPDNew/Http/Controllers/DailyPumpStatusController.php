<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Http\Requests\DailyPumpStatusAssignmentUpdateRequest;
use Modules\PetroPDNew\Http\Requests\DailyPumpStatusCancelRequest;
use Modules\PetroPDNew\Http\Requests\DailyPumpStatusShiftStoreRequest;
use Modules\PetroPDNew\Services\PdnewContextService;
use Modules\PetroPDNew\Services\PdnewDailyPumpStatusActionService;

class DailyPumpStatusController extends PdnewController
{
    public function __construct(
        PdnewContextService $context,
        private PdnewDailyPumpStatusActionService $dailyStatus
    ) {
        parent::__construct($context);
    }

    public function assignModal()
    {
        $data = $this->dailyStatus->assignForm(
            $this->context->businessId(),
            $this->context->locationId()
        );

        return view('petropdnew::operators.daily-pump-status-modal', array_merge($data, [
            'mode' => 'assign',
        ]));
    }

    public function store(DailyPumpStatusShiftStoreRequest $request)
    {
        try {
            $activeLocationId = $this->context->locationId();
            $requestedLocationId = (int) $request->input('location_id', 0) ?: null;
            if (! $activeLocationId && $requestedLocationId) {
                $this->context->authorizeLocation($requestedLocationId);
            }

            $shift = $this->dailyStatus->createShift(
                $this->context->businessId(),
                $activeLocationId,
                $request->validated(),
                $this->context->userId()
            );

            return back()->with('success', sprintf(
                'Shift %s was created with %d pump assignment(s).',
                $shift->shift_number,
                $shift->assignments()->count()
            ));
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function editModal(int $assignment)
    {
        $model = $this->dailyStatus->assignment(
            $this->context->businessId(),
            $this->context->locationId(),
            $assignment
        );

        return view('petropdnew::operators.daily-pump-status-modal', array_merge(
            $this->dailyStatus->assignmentForm($model),
            ['mode' => 'edit']
        ));
    }

    public function update(DailyPumpStatusAssignmentUpdateRequest $request, int $assignment)
    {
        try {
            $model = $this->dailyStatus->assignment(
                $this->context->businessId(),
                $this->context->locationId(),
                $assignment
            );
            $this->dailyStatus->updateAssignment($model, $request->validated(), $this->context->userId());

            return back()->with('success', 'The unreceived pump assignment was updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function cancelModal(int $assignment)
    {
        $model = $this->dailyStatus->assignment(
            $this->context->businessId(),
            $this->context->locationId(),
            $assignment
        );

        return view('petropdnew::operators.daily-pump-status-modal', array_merge(
            $this->dailyStatus->assignmentForm($model),
            ['mode' => 'cancel']
        ));
    }

    public function destroy(DailyPumpStatusCancelRequest $request, int $assignment)
    {
        try {
            $model = $this->dailyStatus->assignment(
                $this->context->businessId(),
                $this->context->locationId(),
                $assignment
            );
            $this->dailyStatus->cancelAssignment(
                $model,
                (string) $request->validated('reason'),
                $this->context->userId()
            );

            return back()->with('success', 'The unreceived pump assignment was cancelled.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
