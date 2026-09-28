<?php

namespace Modules\HRManager\Services;

use Illuminate\Support\Facades\DB;
use Modules\HRManager\Models\HrDepartment;
use Modules\HRManager\Models\HrDesignation;
use Modules\HRManager\Models\HrShift;
use Modules\HRManager\Models\HrHoliday;
use Modules\HRManager\Models\HrWeeklyOff;

class HrSetupService
{
    protected function businessId(): ?int
    {
        return session('business.id') ?? session('business_id') ?? request()->session()->get('user.business_id');
    }

    protected function actorId(): ?int
    {
        return auth()->id();
    }

    public function dashboardCounts(): array
    {
        $businessId = $this->businessId();
        return [
            'departments' => HrDepartment::where('business_id', $businessId)->count(),
            'designations' => HrDesignation::where('business_id', $businessId)->count(),
            'shifts' => HrShift::where('business_id', $businessId)->count(),
            'holidays' => HrHoliday::where('business_id', $businessId)->count(),
            'weekly_offs' => HrWeeklyOff::where('business_id', $businessId)->count(),
        ];
    }

    public function departments()
    {
        return HrDepartment::where('business_id', $this->businessId())->latest()->get();
    }

    public function designations()
    {
        return HrDesignation::with('department')->where('business_id', $this->businessId())->latest()->get();
    }

    public function shifts()
    {
        return HrShift::where('business_id', $this->businessId())->latest()->get();
    }

    public function holidays()
    {
        return HrHoliday::where('business_id', $this->businessId())->orderByDesc('holiday_date')->get();
    }

    public function weeklyOffs()
    {
        return HrWeeklyOff::where('business_id', $this->businessId())->orderBy('day_of_week')->get();
    }

    public function saveDepartment(array $data, ?HrDepartment $department = null): HrDepartment
    {
        $data['business_id'] = $this->businessId();
        $data[$department ? 'updated_by' : 'created_by'] = $this->actorId();
        $department = $department ?: new HrDepartment();
        $department->fill($data)->save();
        return $department;
    }

    public function saveDesignation(array $data, ?HrDesignation $designation = null): HrDesignation
    {
        $data['business_id'] = $this->businessId();
        $data[$designation ? 'updated_by' : 'created_by'] = $this->actorId();
        $designation = $designation ?: new HrDesignation();
        $designation->fill($data)->save();
        return $designation;
    }

    public function saveShift(array $data, ?HrShift $shift = null): HrShift
    {
        $data['business_id'] = $this->businessId();
        $data['is_night_shift'] = !empty($data['is_night_shift']) ? 1 : 0;
        $data[$shift ? 'updated_by' : 'created_by'] = $this->actorId();
        $shift = $shift ?: new HrShift();
        $shift->fill($data)->save();
        return $shift;
    }

    public function saveHoliday(array $data, ?HrHoliday $holiday = null): HrHoliday
    {
        $data['business_id'] = $this->businessId();
        $data['is_paid'] = !empty($data['is_paid']) ? 1 : 0;
        $data[$holiday ? 'updated_by' : 'created_by'] = $this->actorId();
        $holiday = $holiday ?: new HrHoliday();
        $holiday->fill($data)->save();
        return $holiday;
    }

    public function saveWeeklyOff(array $data, ?HrWeeklyOff $weeklyOff = null): HrWeeklyOff
    {
        $data['business_id'] = $this->businessId();
        $data[$weeklyOff ? 'updated_by' : 'created_by'] = $this->actorId();
        $weeklyOff = $weeklyOff ?: new HrWeeklyOff();
        $weeklyOff->fill($data)->save();
        return $weeklyOff;
    }
}
