<?php

namespace Modules\AutoService\Services\Workflow;

use Illuminate\Support\Facades\DB;

class VehicleHistoryService
{
    public function ownerView(int $vehicleId): array
    {
        return [
            'vehicle' => DB::table('auto_service_vehicles')->where('id', $vehicleId)->first(),
            'jobs' => DB::table('auto_service_jobs')->where('vehicle_id', $vehicleId)->orderByDesc('job_date')->get(),
            'timeline' => DB::table('auto_service_timeline')->where('vehicle_id', $vehicleId)->orderByDesc('event_at')->get(),
            'invoices' => DB::table('auto_service_invoices')->where('vehicle_id', $vehicleId)->orderByDesc('id')->get(),
            'payments' => DB::table('auto_service_payments')->whereIn('job_id', function ($q) use ($vehicleId) {
                $q->select('id')->from('auto_service_jobs')->where('vehicle_id', $vehicleId);
            })->orderByDesc('id')->get(),
        ];
    }

    public function workshopSafeView(int $vehicleId): array
    {
        $jobs = DB::table('auto_service_jobs')
            ->select('id', 'vehicle_id', 'job_date', 'job_type', 'odometer', 'status', 'customer_complaint')
            ->where('vehicle_id', $vehicleId)
            ->orderByDesc('job_date')
            ->get();

        $jobIds = $jobs->pluck('id')->all();

        $lines = DB::table('auto_service_job_lines')
            ->select('job_id', 'line_type', 'description', 'quantity')
            ->whereIn('job_id', $jobIds)
            ->whereIn('line_type', ['part', 'product', 'lubricant', 'oil', 'service'])
            ->get()
            ->groupBy('job_id');

        return [
            'jobs' => $jobs,
            'technical_lines' => $lines,
        ];
    }
}
