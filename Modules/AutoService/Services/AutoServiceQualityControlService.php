<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;

class AutoServiceQualityControlService
{
    public function listPending($businessId = null)
    {
        return DB::table('auto_service_jobs')
            ->leftJoin('auto_service_vehicles', 'auto_service_jobs.vehicle_id', '=', 'auto_service_vehicles.id')
            ->select('auto_service_jobs.*', 'auto_service_vehicles.registration_no')
            ->when($businessId, fn($q) => $q->where('auto_service_jobs.business_id', $businessId))
            ->whereIn('auto_service_jobs.status', ['repair_completed', 'quality_check', 'ready'])
            ->orderByDesc('auto_service_jobs.id');
    }

    public function saveCheck(int $jobId, array $data, $businessId = null, $locationId = null): int
    {
        $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
        $payload = [
            'business_id' => $businessId ?: ($job->business_id ?? null),
            'location_id' => $locationId ?: ($job->location_id ?? null),
            'job_id' => $jobId,
            'vehicle_id' => $job->vehicle_id ?? null,
            'qc_no' => $data['qc_no'] ?? ('QC-' . str_pad((string)$jobId, 6, '0', STR_PAD_LEFT)),
            'qc_date' => $data['qc_date'] ?? now()->toDateString(),
            'status' => $data['status'] ?? 'passed',
            'mechanical_checked' => !empty($data['mechanical_checked']) ? 1 : 0,
            'electrical_checked' => !empty($data['electrical_checked']) ? 1 : 0,
            'road_test_done' => !empty($data['road_test_done']) ? 1 : 0,
            'wash_done' => !empty($data['wash_done']) ? 1 : 0,
            'customer_concern_verified' => !empty($data['customer_concern_verified']) ? 1 : 0,
            'remarks' => $data['remarks'] ?? null,
            'checked_by' => auth()->id(),
            'checked_at' => now(),
            'updated_at' => now(),
        ];

        $existing = DB::table('auto_service_quality_checks')->where('job_id', $jobId)->latest('id')->first();
        if ($existing) {
            DB::table('auto_service_quality_checks')->where('id', $existing->id)->update($payload);
            $id = $existing->id;
        } else {
            $payload['created_at'] = now();
            $id = DB::table('auto_service_quality_checks')->insertGetId($payload);
        }

        DB::table('auto_service_jobs')->where('id', $jobId)->update([
            'qc_status' => $payload['status'],
            'status' => $payload['status'] === 'passed' ? 'ready' : 'quality_check',
            'workflow_stage' => $payload['status'] === 'passed' ? 'ready' : 'quality_check',
            'job_progress' => $payload['status'] === 'passed' ? 90 : 75,
            'updated_at' => now(),
        ]);

        DB::table('auto_service_timeline')->insert([
            'business_id' => $payload['business_id'],
            'location_id' => $payload['location_id'],
            'vehicle_id' => $payload['vehicle_id'],
            'job_id' => $jobId,
            'event_type' => 'quality_control',
            'title' => 'Quality control ' . ucfirst($payload['status']),
            'description' => $payload['remarks'],
            'event_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
