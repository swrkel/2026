<?php

namespace Modules\AutoService\Services\Workflow;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Services\AutoServiceNumberService;
use Modules\AutoService\Services\Central\CentralVehicleRegistryService;

class ReceptionWorkflowService
{
    public function __construct(
        protected AutoServiceNumberService $numbers,
        protected CentralVehicleRegistryService $centralRegistry
    ) {}

    public function searchVehicle(?int $businessId, string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $tenantVehicles = DB::table('auto_service_vehicles')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->where(function ($q) use ($term) {
                $q->where('registration_no', 'like', "%{$term}%")
                    ->orWhere('vin', 'like', "%{$term}%")
                    ->orWhere('engine_no', 'like', "%{$term}%")
                    ->orWhere('chassis_no', 'like', "%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($row) => [
                'source' => 'tenant',
                'id' => $row->id,
                'registration_no' => $row->registration_no,
                'make' => $row->make,
                'model' => $row->model,
                'current_odometer' => $row->current_odometer,
            ])
            ->all();

        return $tenantVehicles;
    }

    public function createReceptionAndOptionalJob(array $data, bool $createJob = false): array
    {
        return DB::transaction(function () use ($data, $createJob) {
            $now = now();
            $businessId = $data['business_id'] ?? null;
            $locationId = $data['location_id'] ?? null;

            $receptionId = DB::table('auto_service_receptions')->insertGetId([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'contact_id' => $data['contact_id'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'reception_no' => $this->numbers->nextReceptionNo($businessId),
                'received_at' => $data['received_at'] ?? $now,
                'odometer' => $data['odometer'] ?? null,
                'fuel_level' => $data['fuel_level'] ?? null,
                'customer_complaint' => $data['customer_complaint'] ?? null,
                'advisor_remarks' => $data['advisor_remarks'] ?? null,
                'accessories_received' => $data['accessories_received'] ?? null,
                'existing_damage' => $data['existing_damage'] ?? null,
                'status' => 'received',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $jobId = null;
            if ($createJob && !empty($data['vehicle_id'])) {
                $jobId = DB::table('auto_service_jobs')->insertGetId([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'contact_id' => $data['contact_id'] ?? null,
                    'vehicle_id' => $data['vehicle_id'],
                    'job_no' => $this->numbers->nextJobNo($businessId),
                    'job_date' => today(),
                    'job_type' => $data['job_type'] ?? 'service',
                    'odometer' => $data['odometer'] ?? null,
                    'status' => 'received',
                    'workflow_stage' => 'received',
                    'customer_complaint' => $data['customer_complaint'] ?? null,
                    'advisor_notes' => $data['advisor_remarks'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('auto_service_receptions')->where('id', $receptionId)->update(['job_id' => $jobId, 'updated_at' => $now]);
            }

            $this->recordTimeline($businessId, $locationId, $data['vehicle_id'] ?? null, $jobId, 'reception', 'Vehicle received', $data['customer_complaint'] ?? null);

            return ['reception_id' => $receptionId, 'job_id' => $jobId];
        });
    }

    protected function recordTimeline(?int $businessId, ?int $locationId, ?int $vehicleId, ?int $jobId, string $type, string $title, ?string $description = null): void
    {
        if (!$vehicleId) {
            return;
        }
        DB::table('auto_service_timeline')->insert([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'vehicle_id' => $vehicleId,
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
