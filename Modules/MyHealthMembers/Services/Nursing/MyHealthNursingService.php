<?php

namespace Modules\MyHealthMembers\Services\Nursing;

use Illuminate\Support\Facades\Auth;
use Modules\MyHealthMembers\Entities\MyHealthMedicationAdministration;
use Modules\MyHealthMembers\Entities\MyHealthNursingCarePlan;
use Modules\MyHealthMembers\Entities\MyHealthNursingHandover;
use Modules\MyHealthMembers\Entities\MyHealthNursingNote;
use Modules\MyHealthMembers\Entities\MyHealthNursingVitalSign;

class MyHealthNursingService
{
    public function dashboardCounts(): array
    {
        return [
            'vitals_pending' => MyHealthNursingVitalSign::whereDate('created_at', today())->count(),
            'medications_due' => MyHealthMedicationAdministration::where('status', 'due')->count(),
            'critical_alerts' => MyHealthNursingVitalSign::where('status', 'critical')->count(),
            'handovers' => MyHealthNursingHandover::whereDate('created_at', today())->count(),
        ];
    }

    public function calculateBmi($weight, $feet, $inches): ?float
    {
        $weight = (float) $weight;
        $heightInches = ((int) $feet * 12) + (int) $inches;

        if ($weight <= 0 || $heightInches <= 0) {
            return null;
        }

        $meters = $heightInches * 0.0254;
        return round($weight / ($meters * $meters), 2);
    }

    public function vitalStatus(array $data): string
    {
        $systolic = (int) ($data['systolic_bp'] ?? 0);
        $diastolic = (int) ($data['diastolic_bp'] ?? 0);
        $spo2 = (int) ($data['spo2'] ?? 0);
        $pulse = (int) ($data['pulse'] ?? 0);
        $temperature = (float) ($data['temperature'] ?? 0);

        if ($systolic >= 180 || $diastolic >= 120 || ($spo2 > 0 && $spo2 < 90) || $pulse > 130 || $temperature >= 39.5) {
            return 'critical';
        }

        if ($systolic >= 140 || $diastolic >= 90 || ($spo2 > 0 && $spo2 < 95) || $pulse > 110 || $temperature >= 38) {
            return 'warning';
        }

        return 'normal';
    }

    public function createVitalSign(array $data): MyHealthNursingVitalSign
    {
        $data['recorded_by'] = Auth::id();
        $data['recorded_at'] = $data['recorded_at'] ?? now();
        $data['bmi'] = $this->calculateBmi($data['weight'] ?? null, $data['height_feet'] ?? null, $data['height_inches'] ?? null);
        $data['status'] = $this->vitalStatus($data);

        return MyHealthNursingVitalSign::create($data);
    }

    public function createNote(array $data): MyHealthNursingNote
    {
        $data['nurse_id'] = Auth::id();
        $data['noted_at'] = $data['noted_at'] ?? now();
        $data['status'] = $data['status'] ?? 'active';

        return MyHealthNursingNote::create($data);
    }

    public function createMedicationAdministration(array $data): MyHealthMedicationAdministration
    {
        $data['nurse_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'due';

        return MyHealthMedicationAdministration::create($data);
    }

    public function createCarePlan(array $data): MyHealthNursingCarePlan
    {
        $data['nurse_id'] = Auth::id();
        $data['status'] = $data['status'] ?? 'active';

        return MyHealthNursingCarePlan::create($data);
    }

    public function createHandover(array $data): MyHealthNursingHandover
    {
        $data['from_nurse_id'] = Auth::id();
        $data['handover_at'] = $data['handover_at'] ?? now();
        $data['status'] = $data['status'] ?? 'submitted';

        return MyHealthNursingHandover::create($data);
    }
}
