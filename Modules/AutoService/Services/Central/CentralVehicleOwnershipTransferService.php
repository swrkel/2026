<?php

namespace Modules\AutoService\Services\Central;

use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicle;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleOwner;
use Modules\AutoService\Entities\Central\AutoServiceCentralVehicleOwnershipTransfer;

class CentralVehicleOwnershipTransferService
{
    public function requestTransfer(AutoServiceCentralVehicle $vehicle, array $newOwner, ?int $businessId = null, ?string $tenantKey = null): AutoServiceCentralVehicleOwnershipTransfer
    {
        $currentOwner = $vehicle->currentOwner;

        return AutoServiceCentralVehicleOwnershipTransfer::create([
            'central_vehicle_id' => $vehicle->id,
            'previous_owner_id' => optional($currentOwner)->id,
            'previous_owner_mobile' => optional($currentOwner)->mobile,
            'new_owner_name' => $newOwner['owner_name'] ?? null,
            'new_owner_mobile' => $this->normalizeMobile($newOwner['mobile'] ?? ''),
            'new_owner_email' => $newOwner['email'] ?? null,
            'new_owner_nic_no' => $newOwner['nic_no'] ?? null,
            'new_owner_address' => $newOwner['address'] ?? null,
            'requested_by_business_id' => $businessId,
            'requested_by_tenant' => $tenantKey,
            'status' => 'pending_previous_owner_approval',
            'requested_at' => now(),
        ]);
    }

    public function approveByPreviousOwner(AutoServiceCentralVehicleOwnershipTransfer $transfer): AutoServiceCentralVehicleOwnershipTransfer
    {
        $transfer->status = 'pending_new_owner_verification';
        $transfer->previous_owner_approved_at = now();
        $transfer->save();
        return $transfer;
    }

    public function completeAfterNewOwnerOtp(AutoServiceCentralVehicleOwnershipTransfer $transfer): AutoServiceCentralVehicleOwner
    {
        return DB::connection(config('autoservice.central_connection', config('database.default')))->transaction(function () use ($transfer) {
            $vehicle = AutoServiceCentralVehicle::with('currentOwner')->findOrFail($transfer->central_vehicle_id);

            AutoServiceCentralVehicleOwner::where('central_vehicle_id', $vehicle->id)
                ->where('is_current_owner', 1)
                ->update([
                    'is_current_owner' => 0,
                    'owned_to' => now()->toDateString(),
                ]);

            $owner = AutoServiceCentralVehicleOwner::create([
                'central_vehicle_id' => $vehicle->id,
                'owner_name' => $transfer->new_owner_name,
                'mobile' => $this->normalizeMobile($transfer->new_owner_mobile),
                'email' => $transfer->new_owner_email,
                'nic_no' => $transfer->new_owner_nic_no,
                'address' => $transfer->new_owner_address,
                'owned_from' => now()->toDateString(),
                'is_current_owner' => 1,
                'is_verified' => 1,
                'verified_at' => now(),
                'created_by_business_id' => $transfer->requested_by_business_id,
                'created_by_tenant' => $transfer->requested_by_tenant,
            ]);

            $vehicle->last_registered_owner_id = $owner->id;
            $vehicle->save();

            $transfer->new_owner_id = $owner->id;
            $transfer->status = 'completed';
            $transfer->completed_at = now();
            $transfer->save();

            return $owner;
        });
    }

    public function normalizeMobile(string $mobile): string
    {
        return preg_replace('/[^0-9+]/', '', trim($mobile));
    }
}
