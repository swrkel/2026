<?php

namespace Modules\AutoService\Services\Central;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServiceJob;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicle;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleServiceRecord;
use Modules\AutoService\Services\Central\CentralVehiclePrivacyService;

class CentralVehicleServiceRecordSyncService
{
    public function syncFromJob(AutoServiceJob $job): ?AutoServiceCentralVehicleServiceRecord
    {
        $vehicle = AutoServiceVehicle::find($job->vehicle_id);
        if (!$vehicle) { return null; }

        $centralVehicle = $this->resolveCentralVehicle($vehicle);
        if (!$centralVehicle) { return null; }

        $invoice = class_exists(AutoServiceInvoice::class)
            ? AutoServiceInvoice::where('job_id', $job->id)->orderByDesc('id')->first()
            : null;

        $rawPartsUsed = DB::getSchemaBuilder()->hasTable('auto_service_job_lines')
            ? DB::table('auto_service_job_lines')->where('job_id', $job->id)->where('line_type', 'part')->get()->toArray()
            : [];

        $privacy = app(CentralVehiclePrivacyService::class);
        $partsUsed = $privacy->safePartsList($rawPartsUsed);
        $oilsUsed = collect($partsUsed)->filter(function ($line) {
            return stripos((string)($line['description'] ?? $line['product_name'] ?? ''), 'oil') !== false
                || stripos((string)($line['oil_grade'] ?? ''), 'w') !== false;
        })->values()->all();

        $record = AutoServiceCentralVehicleServiceRecord::updateOrCreate([
            'tenant_key' => config('database.default'),
            'local_job_id' => $job->id,
        ], [
            'central_vehicle_id' => $centralVehicle->id,
            'business_id' => $job->business_id ?? request()->session()->get('user.business_id'),
            'business_name' => optional(request()->session()->get('business'))->name,
            'local_vehicle_id' => $vehicle->id,
            'local_invoice_id' => $invoice->id ?? null,
            'job_no' => $job->job_no ?? $job->id,
            'invoice_no' => $invoice->invoice_no ?? null,
            'service_date' => $job->job_date ?? now()->toDateString(),
            'mileage' => $job->mileage ?? $vehicle->mileage ?? 0,
            'service_type' => $job->service_type ?? $job->job_type ?? null,
            'complaints' => $job->complaint ?? $job->customer_complaint ?? null,
            'diagnosis' => $job->diagnosis ?? null,
            'work_done' => $job->work_done ?? $job->description ?? null,
            // Store only non-financial product/lubricant data in central service history.
            // Prices, invoice line amounts, previous workshop contact details and sensitive tenant data must not be exposed to other workshops.
            'parts_used' => $partsUsed,
            'oils_used' => $oilsUsed,
            'labour_total' => $invoice->labour_total ?? 0,
            'parts_total' => $invoice->parts_total ?? 0,
            'oil_total' => 0,
            'discount_total' => $invoice->discount_total ?? 0,
            'tax_total' => $invoice->tax_total ?? 0,
            'grand_total' => $invoice->grand_total ?? $invoice->final_total ?? 0,
            'advisor_name' => $job->advisor_name ?? null,
            'mechanic_names' => $job->mechanic_names ?? null,
            'posted_at' => now(),
        ]);

        if (!empty($record->mileage) && $record->mileage > $centralVehicle->last_mileage) {
            $centralVehicle->last_mileage = $record->mileage;
            $centralVehicle->save();
        }

        return $record;
    }

    protected function resolveCentralVehicle(AutoServiceVehicle $vehicle): ?AutoServiceCentralVehicle
    {
        return AutoServiceCentralVehicle::where(function ($q) use ($vehicle) {
            if (!empty($vehicle->registration_no)) { $q->orWhere('registration_no', $vehicle->registration_no); }
            if (!empty($vehicle->vin)) { $q->orWhere('vin', $vehicle->vin); }
            if (!empty($vehicle->chassis_no)) { $q->orWhere('chassis_no', $vehicle->chassis_no); }
        })->first();
    }
}
