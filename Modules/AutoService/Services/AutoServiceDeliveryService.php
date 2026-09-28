<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;

class AutoServiceDeliveryService
{
    public function listReady($businessId = null)
    {
        return DB::table('auto_service_jobs')
            ->leftJoin('auto_service_vehicles', 'auto_service_jobs.vehicle_id', '=', 'auto_service_vehicles.id')
            ->select('auto_service_jobs.*', 'auto_service_vehicles.registration_no')
            ->when($businessId, fn($q) => $q->where('auto_service_jobs.business_id', $businessId))
            ->whereIn('auto_service_jobs.status', ['ready', 'delivered'])
            ->orderByDesc('auto_service_jobs.id');
    }

    public function deliver(int $jobId, array $data, $businessId = null, $locationId = null): int
    {
        $job = DB::table('auto_service_jobs')->where('id', $jobId)->first();
        $outstanding = (float)($job->balance_amount ?? 0);
        $payload = [
            'business_id' => $businessId ?: ($job->business_id ?? null),
            'location_id' => $locationId ?: ($job->location_id ?? null),
            'job_id' => $jobId,
            'vehicle_id' => $job->vehicle_id ?? null,
            'delivery_no' => $data['delivery_no'] ?? ('DEL-' . str_pad((string)$jobId, 6, '0', STR_PAD_LEFT)),
            'delivered_at' => $data['delivered_at'] ?? now(),
            'status' => 'delivered',
            'outstanding_amount' => $outstanding,
            'invoice_confirmed' => !empty($data['invoice_confirmed']) ? 1 : 0,
            'payment_confirmed' => !empty($data['payment_confirmed']) ? 1 : 0,
            'vehicle_handover_confirmed' => !empty($data['vehicle_handover_confirmed']) ? 1 : 0,
            'customer_signature' => $data['customer_signature'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'delivered_by' => auth()->id(),
            'updated_at' => now(),
        ];

        $existing = DB::table('auto_service_deliveries')->where('job_id', $jobId)->latest('id')->first();
        if ($existing) {
            DB::table('auto_service_deliveries')->where('id', $existing->id)->update($payload);
            $id = $existing->id;
        } else {
            $payload['created_at'] = now();
            $id = DB::table('auto_service_deliveries')->insertGetId($payload);
        }

        DB::table('auto_service_jobs')->where('id', $jobId)->update([
            'delivery_status' => 'delivered',
            'status' => 'delivered',
            'workflow_stage' => 'delivered',
            'job_progress' => 100,
            'updated_at' => now(),
        ]);

        DB::table('auto_service_timeline')->insert([
            'business_id' => $payload['business_id'],
            'location_id' => $payload['location_id'],
            'vehicle_id' => $payload['vehicle_id'],
            'job_id' => $jobId,
            'event_type' => 'delivery',
            'title' => 'Vehicle delivered',
            'description' => $payload['remarks'],
            'event_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}
