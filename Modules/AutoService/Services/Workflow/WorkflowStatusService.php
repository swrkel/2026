<?php

namespace Modules\AutoService\Services\Workflow;

use Illuminate\Support\Facades\DB;

class WorkflowStatusService
{
    public const STAGES = [
        'received' => 'Received',
        'inspection' => 'Inspection',
        'estimate' => 'Estimate',
        'approval' => 'Customer Approval',
        'waiting_parts' => 'Waiting Parts',
        'repair' => 'Repair In Progress',
        'qc' => 'Quality Check',
        'wash' => 'Wash',
        'ready' => 'Ready for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ];

    public function stages(): array
    {
        return self::STAGES;
    }

    public function updateJobStage(int $jobId, string $stage, ?string $note = null, ?int $userId = null): void
    {
        if (!array_key_exists($stage, self::STAGES)) {
            throw new \InvalidArgumentException('Invalid Auto Service workflow stage.');
        }

        DB::transaction(function () use ($jobId, $stage, $note, $userId) {
            $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
            if (!$job) {
                throw new \RuntimeException('Auto Service job not found.');
            }

            DB::table('auto_service_jobs')->where('id', $jobId)->update([
                'status' => $stage,
                'workflow_stage' => $stage,
                'updated_at' => now(),
            ]);

            DB::table('auto_service_timeline')->insert([
                'business_id' => $job->business_id ?? null,
                'location_id' => $job->location_id ?? null,
                'vehicle_id' => $job->vehicle_id,
                'job_id' => $jobId,
                'event_type' => 'workflow',
                'title' => self::STAGES[$stage],
                'description' => $note,
                'event_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
