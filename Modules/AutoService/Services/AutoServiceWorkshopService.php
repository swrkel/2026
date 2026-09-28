<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceJobMechanic;
use Modules\AutoService\Entities\AutoServicePartMovement;
use Modules\AutoService\Entities\AutoServiceTimeline;

class AutoServiceWorkshopService
{
    public function changeStatus(AutoServiceJob $job, string $status, ?string $note = null)
    {
        return DB::transaction(function () use ($job, $status, $note) {
            $job->status = $status;
            if ($status === 'in_progress' && empty($job->work_started_at)) $job->work_started_at = now();
            if ($status === 'completed' && empty($job->work_completed_at)) $job->work_completed_at = now();
            if ($status === 'quality_check' && empty($job->quality_checked_at)) $job->quality_checked_at = now();
            if ($status === 'ready' && empty($job->ready_at)) $job->ready_at = now();
            if ($status === 'delivered' && empty($job->delivered_at)) $job->delivered_at = now();
            if ($note) $job->delivery_note = trim(($job->delivery_note ? $job->delivery_note."\n" : '').$note);
            $job->save();
            $this->timeline($job, 'status_'.$status, 'Job status changed to '.ucwords(str_replace('_',' ', $status)), $note);
            return $job;
        });
    }

    public function assignMechanics(AutoServiceJob $job, array $rows)
    {
        return DB::transaction(function () use ($job, $rows) {
            AutoServiceJobMechanic::where('job_id',$job->id)->delete();
            foreach ($rows as $row) {
                if (empty($row['mechanic_id'])) continue;
                AutoServiceJobMechanic::create([
                    'business_id'=>$job->business_id,
                    'job_id'=>$job->id,
                    'mechanic_id'=>$row['mechanic_id'],
                    'assigned_at'=>$row['assigned_at'] ?? now(),
                    'estimated_hours'=>(float)($row['estimated_hours'] ?? 0),
                    'actual_hours'=>(float)($row['actual_hours'] ?? 0),
                    'status'=>$row['status'] ?? 'assigned',
                    'note'=>$row['note'] ?? null,
                ]);
            }
            $this->timeline($job, 'mechanics_assigned', 'Mechanics assigned', 'Mechanic assignment updated.');
        });
    }

    public function savePartMovements(AutoServiceJob $job, array $rows)
    {
        // Stage 022: use the dedicated parts/labour engine so job lines and totals
        // remain consistent with invoices, print views and reports.
        return app(AutoServicePartsLabourService::class)->saveParts($job, $rows, true);
    }

    private function timeline(AutoServiceJob $job, string $type, string $title, ?string $description = null)
    {
        AutoServiceTimeline::create(['business_id'=>$job->business_id,'location_id'=>$job->location_id,'vehicle_id'=>$job->vehicle_id,'job_id'=>$job->id,'event_type'=>$type,'title'=>$title,'description'=>$description,'event_at'=>now()]);
    }
}
