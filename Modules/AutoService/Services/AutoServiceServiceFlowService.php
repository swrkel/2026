<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;

class AutoServiceServiceFlowService
{
    public function board($businessId = null, $locationId = null)
    {
        $qualityChecks = DB::table('auto_service_quality_checks')
            ->select('job_id', DB::raw('MAX(status) as latest_qc_status'))
            ->groupBy('job_id');

        $deliveries = DB::table('auto_service_deliveries')
            ->select('job_id', DB::raw('MAX(status) as latest_delivery_status'))
            ->groupBy('job_id');

        $mechanicCounts = DB::table('auto_service_job_mechanics')
            ->select(
                'job_id',
                DB::raw('COUNT(*) as assigned_mechanics'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_mechanics')
            )
            ->groupBy('job_id');

        $inspectionCounts = DB::table('auto_service_inspections')
            ->select('job_id', DB::raw('COUNT(*) as inspection_count'))
            ->groupBy('job_id');

        return DB::table('auto_service_jobs as j')
            ->leftJoin('auto_service_vehicles as v', 'j.vehicle_id', '=', 'v.id')
            ->leftJoinSub($qualityChecks, 'qc_latest', function ($join) {
                $join->on('qc_latest.job_id', '=', 'j.id');
            })
            ->leftJoinSub($deliveries, 'delivery_latest', function ($join) {
                $join->on('delivery_latest.job_id', '=', 'j.id');
            })
            ->leftJoinSub($mechanicCounts, 'mechanic_counts', function ($join) {
                $join->on('mechanic_counts.job_id', '=', 'j.id');
            })
            ->leftJoinSub($inspectionCounts, 'inspection_counts', function ($join) {
                $join->on('inspection_counts.job_id', '=', 'j.id');
            })
            ->select(
                'j.*',
                'v.registration_no',
                'qc_latest.latest_qc_status',
                'delivery_latest.latest_delivery_status',
                DB::raw('COALESCE(mechanic_counts.assigned_mechanics, 0) as assigned_mechanics'),
                DB::raw('COALESCE(mechanic_counts.completed_mechanics, 0) as completed_mechanics'),
                DB::raw('COALESCE(inspection_counts.inspection_count, 0) as inspection_count')
            )
            ->when($businessId, fn($q) => $q->where('j.business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) {
                $qq->whereNull('j.location_id')->orWhere('j.location_id', $locationId);
            }))
            ->whereNotIn('j.status', ['cancelled'])
            ->orderByDesc('j.id');
    }

    public function mechanics($businessId = null, $locationId = null)
    {
        return DB::table('auto_service_mechanics')
            ->when($businessId, fn($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn($q) => $q->where(function ($qq) use ($locationId) {
                $qq->whereNull('location_id')->orWhere('location_id', $locationId);
            }))
            ->where(function ($q) {
                $q->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->orderBy('name')
            ->get();
    }

    public function assignMechanic(int $jobId, int $mechanicId, array $data, $businessId = null, $locationId = null): int
    {
        $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
        $payload = [
            'business_id' => $businessId ?: ($job->business_id ?? null),
            'location_id' => $locationId ?: ($job->location_id ?? null),
            'job_id' => $jobId,
            'mechanic_id' => $mechanicId,
            'assigned_at' => now(),
            'estimated_hours' => (float)($data['estimated_hours'] ?? 0),
            'status' => 'assigned',
            'note' => $data['note'] ?? null,
            'updated_at' => now(),
        ];

        $existing = DB::table('auto_service_job_mechanics')
            ->where('job_id', $jobId)
            ->where('mechanic_id', $mechanicId)
            ->first();

        if ($existing) {
            DB::table('auto_service_job_mechanics')->where('id', $existing->id)->update($payload);
            $id = $existing->id;
        } else {
            $payload['created_at'] = now();
            $id = DB::table('auto_service_job_mechanics')->insertGetId($payload);
        }

        DB::table('auto_service_jobs')->where('id', $jobId)->update([
            'status' => 'assigned',
            'workflow_stage' => 'assigned',
            'job_progress' => DB::raw('GREATEST(COALESCE(job_progress,0), 25)'),
            'updated_at' => now(),
        ]);

        $this->timeline($job, $jobId, 'technician_assignment', 'Technician assigned', $payload['note']);
        return $id;
    }

    public function updateMechanicStatus(int $assignmentId, string $status, array $data = []): void
    {
        $assignment = DB::table('auto_service_job_mechanics')->where('id', $assignmentId)->first();
        if (!$assignment) {
            return;
        }

        $payload = [
            'status' => $status,
            'actual_hours' => (float)($data['actual_hours'] ?? ($assignment->actual_hours ?? 0)),
            'note' => $data['note'] ?? $assignment->note,
            'updated_at' => now(),
        ];
        if ($status === 'in_progress') {
            $payload['started_at'] = $assignment->started_at ?: now();
        }
        if ($status === 'completed') {
            $payload['completed_at'] = now();
        }

        DB::table('auto_service_job_mechanics')->where('id', $assignmentId)->update($payload);

        $job = DB::table('auto_service_jobs')->where('id', $assignment->job_id)->first();
        $jobStatus = $status === 'completed' ? 'repair_completed' : 'in_progress';
        DB::table('auto_service_jobs')->where('id', $assignment->job_id)->update([
            'status' => $jobStatus,
            'workflow_stage' => $jobStatus,
            'job_progress' => $status === 'completed' ? DB::raw('GREATEST(COALESCE(job_progress,0), 70)') : DB::raw('GREATEST(COALESCE(job_progress,0), 45)'),
            'updated_at' => now(),
        ]);
        $this->timeline($job, $assignment->job_id, 'technician_status', 'Technician work ' . str_replace('_', ' ', $status), $payload['note']);
    }

    public function saveInspectionCheckpoint(int $jobId, array $data, $businessId = null, $locationId = null): int
    {
        $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
        $inspectionId = DB::table('auto_service_inspections')->insertGetId([
            'business_id' => $businessId ?: ($job->business_id ?? null),
            'location_id' => $locationId ?: ($job->location_id ?? null),
            'contact_id' => $job->contact_id ?? null,
            'vehicle_id' => $job->vehicle_id ?? null,
            'job_id' => $jobId,
            'inspection_no' => 'INSP-' . str_pad((string)$jobId, 6, '0', STR_PAD_LEFT) . '-' . now()->format('His'),
            'inspection_date' => now()->toDateString(),
            'status' => $data['status'] ?? 'completed',
            'odometer' => $data['odometer'] ?? null,
            'fuel_level' => $data['fuel_level'] ?? null,
            'customer_remarks' => $data['customer_remarks'] ?? null,
            'advisor_remarks' => $data['advisor_remarks'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (($data['items'] ?? []) as $item) {
            if (empty($item['item_name'])) {
                continue;
            }
            DB::table('auto_service_inspection_items')->insert([
                'business_id' => $businessId ?: ($job->business_id ?? null),
                'inspection_id' => $inspectionId,
                'section' => $item['section'] ?? 'General',
                'item_name' => $item['item_name'],
                'condition' => $item['condition'] ?? null,
                'note' => $item['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('auto_service_jobs')->where('id', $jobId)->update([
            'workflow_stage' => 'inspection_completed',
            'job_progress' => DB::raw('GREATEST(COALESCE(job_progress,0), 35)'),
            'updated_at' => now(),
        ]);
        $this->timeline($job, $jobId, 'inspection_checkpoint', 'Inspection checkpoint completed', $data['advisor_remarks'] ?? null);
        return $inspectionId;
    }

    public function markReadyForQc(int $jobId, string $remarks = null): void
    {
        $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
        DB::table('auto_service_jobs')->where('id', $jobId)->update([
            'status' => 'quality_check',
            'workflow_stage' => 'quality_check',
            'job_progress' => DB::raw('GREATEST(COALESCE(job_progress,0), 75)'),
            'updated_at' => now(),
        ]);
        $this->timeline($job, $jobId, 'ready_for_qc', 'Job sent to Quality Control', $remarks);
    }

    public function checklistSummary(int $jobId): array
    {
        return [
            'assignments' => DB::table('auto_service_job_mechanics')->where('job_id', $jobId)->count(),
            'completed_assignments' => DB::table('auto_service_job_mechanics')->where('job_id', $jobId)->where('status', 'completed')->count(),
            'inspections' => DB::table('auto_service_inspections')->where('job_id', $jobId)->count(),
            'qc_passed' => DB::table('auto_service_quality_checks')->where('job_id', $jobId)->where('status', 'passed')->exists(),
            'delivery_done' => DB::table('auto_service_deliveries')->where('job_id', $jobId)->where('status', 'delivered')->exists(),
        ];
    }

    protected function timeline($job, int $jobId, string $type, string $title, ?string $description = null): void
    {
        DB::table('auto_service_timeline')->insert([
            'business_id' => $job->business_id ?? null,
            'location_id' => $job->location_id ?? null,
            'vehicle_id' => $job->vehicle_id ?? null,
            'job_id' => $jobId,
            'event_type' => $type,
            'title' => $title,
            'description' => $description,
            'event_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
