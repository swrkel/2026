<?php

namespace Modules\MyHealthMembers\Services;

use Carbon\Carbon;
use Modules\MyHealthMembers\Entities\MyHealthConsultation;
use Modules\MyHealthMembers\Entities\MyHealthDiagnosis;
use Modules\MyHealthMembers\Entities\MyHealthLabRequest;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;

class MyHealthDoctorPortalService
{
    public function dashboardSummary(int $businessId): array
    {
        $today = Carbon::today()->toDateString();

        return [
            'today_consultations' => MyHealthConsultation::where('business_id', $businessId)->whereDate('consultation_date', $today)->count(),
            'waiting_patients' => MyHealthConsultation::where('business_id', $businessId)->whereIn('status', ['waiting', 'open'])->whereDate('consultation_date', $today)->count(),
            'open_consultations' => MyHealthConsultation::where('business_id', $businessId)->where('status', 'open')->count(),
            'follow_ups' => MyHealthConsultation::where('business_id', $businessId)->whereNotNull('follow_up_date')->whereDate('follow_up_date', '>=', $today)->count(),
            'prescriptions_today' => MyHealthPrescription::where('business_id', $businessId)->whereDate('prescription_date', $today)->count(),
            'lab_requests_today' => class_exists(MyHealthLabRequest::class) ? MyHealthLabRequest::where('business_id', $businessId)->whereDate('created_at', $today)->count() : 0,
        ];
    }

    public function todayQueue(int $businessId)
    {
        return MyHealthConsultation::with(['member', 'doctor'])
            ->where('business_id', $businessId)
            ->whereDate('consultation_date', Carbon::today()->toDateString())
            ->orderByRaw("FIELD(status, 'waiting', 'open', 'in_progress', 'completed', 'cancelled')")
            ->orderBy('consultation_time')
            ->paginate(25);
    }

    public function memberSearch(int $businessId, ?string $term)
    {
        return MyHealthMember::query()
            ->when($term, function ($q) use ($term) {
                $q->where(function ($query) use ($term) {
                    $query->where('name', 'like', '%' . $term . '%')
                        ->orWhere('myhealth_code', 'like', '%' . $term . '%')
                        ->orWhere('mobile', 'like', '%' . $term . '%')
                        ->orWhere('nic_no', 'like', '%' . $term . '%')
                        ->orWhere('passport_no', 'like', '%' . $term . '%');
                });
            })
            ->latest()
            ->limit(20)
            ->get();
    }

    public function recentClinicalItems(MyHealthMember $member): array
    {
        return [
            'consultations' => MyHealthConsultation::where('member_id', $member->id)->latest()->limit(10)->get(),
            'diagnoses' => MyHealthDiagnosis::where('member_id', $member->id)->latest()->limit(10)->get(),
            'prescriptions' => MyHealthPrescription::where('member_id', $member->id)->latest()->limit(10)->get(),
        ];
    }
}
