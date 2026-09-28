<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthAllergy;
use Modules\MyHealthMembers\Entities\MyHealthChronicCondition;
use Modules\MyHealthMembers\Entities\MyHealthClinicalAlert;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;

class MyHealthClinicalDecisionService
{
    public function evaluateMember(MyHealthMember $member, ?int $businessId = null, ?int $consultationId = null): array
    {
        $businessId = $businessId ?: (int) (session('business.id') ?? session('user.business_id') ?? optional(auth()->user())->business_id ?? 0);

        $alerts = [];
        $alerts = array_merge($alerts, $this->allergyAlerts($member, $businessId, $consultationId));
        $alerts = array_merge($alerts, $this->duplicateMedicationAlerts($member, $businessId, $consultationId));
        $alerts = array_merge($alerts, $this->chronicConditionAlerts($member, $businessId, $consultationId));

        foreach ($alerts as $alert) {
            MyHealthClinicalAlert::firstOrCreate([
                'business_id' => $businessId,
                'member_id' => $member->id,
                'consultation_id' => $consultationId,
                'alert_type' => $alert['alert_type'],
                'title' => $alert['title'],
                'status' => 'open',
            ], array_merge($alert, [
                'business_id' => $businessId,
                'member_id' => $member->id,
                'consultation_id' => $consultationId,
                'source' => 'system',
                'status' => 'open',
            ]));
        }

        return $this->openAlerts($member->id, $businessId)->toArray();
    }

    public function openAlerts(int $memberId, ?int $businessId = null)
    {
        $query = MyHealthClinicalAlert::where('member_id', $memberId)->where('status', 'open');

        if ($businessId) {
            $query->where(function ($q) use ($businessId) {
                $q->whereNull('business_id')->orWhere('business_id', $businessId);
            });
        }

        return $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->latest('id')
            ->get();
    }

    public function acknowledge(int $alertId): void
    {
        MyHealthClinicalAlert::where('id', $alertId)->update([
            'status' => 'acknowledged',
            'acknowledged_by' => auth()->id(),
            'acknowledged_at' => now(),
        ]);
    }

    protected function allergyAlerts(MyHealthMember $member, int $businessId, ?int $consultationId): array
    {
        $alerts = [];
        $allergies = MyHealthAllergy::where('member_id', $member->id)->where('is_active', 1)->get();
        if ($allergies->isEmpty()) {
            return [];
        }

        $prescriptions = MyHealthPrescription::where('member_id', $member->id)->latest('id')->limit(10)->get();
        foreach ($allergies as $allergy) {
            foreach ($prescriptions as $prescription) {
                $details = strtolower(($prescription->prescription_details ?? '') . ' ' . ($prescription->instructions ?? ''));
                $needle = strtolower($allergy->allergy_name ?? '');
                if ($needle && Str::contains($details, $needle)) {
                    $alerts[] = [
                        'alert_type' => 'allergy',
                        'severity' => in_array($allergy->severity, ['critical', 'high']) ? $allergy->severity : 'high',
                        'title' => 'Allergy Alert: ' . $allergy->allergy_name,
                        'message' => 'This member has an active allergy recorded. Please verify before prescribing or dispensing.',
                    ];
                }
            }
        }

        if (empty($alerts)) {
            foreach ($allergies as $allergy) {
                if (in_array($allergy->severity, ['critical', 'high'])) {
                    $alerts[] = [
                        'alert_type' => 'allergy',
                        'severity' => $allergy->severity,
                        'title' => 'Important Allergy: ' . $allergy->allergy_name,
                        'message' => trim(($allergy->reaction ?? '') . ' ' . ($allergy->notes ?? '')) ?: 'High-risk allergy is recorded for this member.',
                    ];
                }
            }
        }

        return $alerts;
    }

    protected function duplicateMedicationAlerts(MyHealthMember $member, int $businessId, ?int $consultationId): array
    {
        $recent = MyHealthPrescription::where('member_id', $member->id)->latest('id')->limit(8)->pluck('prescription_details')->filter();
        $seen = [];
        $alerts = [];

        foreach ($recent as $details) {
            $tokens = preg_split('/[,;\n]+/', strtolower($details));
            foreach ($tokens as $token) {
                $name = trim(preg_replace('/\s+/', ' ', $token));
                if (strlen($name) < 4) {
                    continue;
                }
                if (isset($seen[$name])) {
                    $alerts[] = [
                        'alert_type' => 'duplicate_medication',
                        'severity' => 'medium',
                        'title' => 'Possible Duplicate Medicine',
                        'message' => 'Possible duplicate medication found in recent prescriptions: ' . $name,
                    ];
                    break 2;
                }
                $seen[$name] = true;
            }
        }

        return $alerts;
    }

    protected function chronicConditionAlerts(MyHealthMember $member, int $businessId, ?int $consultationId): array
    {
        return MyHealthChronicCondition::where('member_id', $member->id)
            ->where('is_active', 1)
            ->whereIn('severity', ['critical', 'high'])
            ->get()
            ->map(function ($condition) {
                return [
                    'alert_type' => 'chronic_condition',
                    'severity' => $condition->severity ?: 'medium',
                    'title' => 'Chronic Condition: ' . $condition->condition_name,
                    'message' => $condition->notes ?: 'Important chronic condition is recorded. Please review before treatment.',
                ];
            })->toArray();
    }
}
